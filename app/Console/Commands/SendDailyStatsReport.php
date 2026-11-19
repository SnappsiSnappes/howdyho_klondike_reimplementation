<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use Carbon\Carbon;
use App\Nextcloud\WorkerBot;

class SendDailyStatsReport extends Command
{
    protected $signature = 'stats:daily-report {--chat=401113887} {--log=.log}';
    protected $description = 'Отправляет ежедневный отчёт по посещаемости в Nextcloud с анализом персистентных посетителей';

    private int $adminChatId;
    private string $logPath;
    private string $logFormatDate = 'd/M/Y';
    private int $persistentDaysThreshold = 3; // Минимум дней для считания "персистентным"
    private int $persistentMinRequests = 50;  // Минимум запросов в день для попадания в отчёт

    public function handle(): int
    {
        $this->adminChatId = (int) $this->option('chat');
        $this->logPath = "/var/log/apache2/" . $this->option('log');

        if (!file_exists($this->logPath)) {
            $this->error("Лог-файл не найден: {$this->logPath}");
            return self::FAILURE;
        }

        $yesterday = Carbon::yesterday()->timezone('Europe/Moscow');
        $datePattern = $yesterday->format($this->logFormatDate);

        $this->info("📊 Анализирую логи за {$datePattern} и предыдущие дни...");

        // Собираем статистику за целевой день + историю для анализа персистентности
        $stats = $this->parseLogs($datePattern, $yesterday);

        if ($stats['total_requests'] === 0 && $stats['bots_filtered'] === 0) {
            $this->info("⚪ Нет записей за {$datePattern}");
            return self::SUCCESS;
        }

        $this->sendReport($stats, $yesterday);
        $this->info("✅ Отчёт отправлен!");

        return self::SUCCESS;
    }

    private function isBot(string $userAgent): bool
    {
        $ua = strtolower($userAgent);

        $botPatterns = [
            // Яндекс
            'yandexbot', 'yandexaccessibility', 'yandeximages', 'yandexvideo', 'yandexmedia',
            'yandexblogs', 'yandexfavicon', 'yandexantivirus', 'yandexmarket', 'yandexwebmaster',
            'yandexdirect', 'yandexverticals',
            // Google
            'googlebot', 'google-inspectiontool', 'google-read-aloud',
            // Bing/Microsoft
            'bingbot', 'msnbot', 'adidxbot', 'linkedinbot',
            // Другие поисковики
            'duckduckbot', 'baiduspider', 'sogou', 'seznambot',
            // SEO-инструменты
            'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'rogerbot', ' SISTRIX', 'cognitiveseo',
            'linkdex', 'majestic',
            // Мониторинг
            'uptimerobot', 'pingdom', 'statuscake', 'site24x7',
            // Утилиты
            'curl', 'wget', 'python-requests', 'go-http-client', 'scrapy', 'httpclient', 'java/', 'libwww',
            // AI-краулеры
            'gptbot', 'chatgpt-user', 'claudebot', 'perplexitybot', 'ccbot', 'diffbot', 'applebot-extended',
            // CDN/Cloud
            'cloudflare', 'akamai', 'fastly',
        ];

        foreach ($botPatterns as $pattern) {
            if (str_contains($ua, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function extractUserAgent(string $line): ?string
    {
        if (preg_match('/"([^"]*)"[^"]*$/', trim($line), $m)) {
            return $m[1];
        }
        return null;
    }

    private function isBotIp(string $ip): bool
    {
        $botRanges = [
            '185.177.72.', '77.88.5.', '5.255.253.', '2a02:6b8:', '141.8.142.',
            '141.8.143.', '37.9.64.', '5.255.250.',
        ];

        foreach ($botRanges as $range) {
            if (str_starts_with($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function extractHour(string $line): ?string
    {
        if (preg_match('/\[\d{2}\/\w{3}\/\d{4}:(\d{2}):/', $line, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractStatusCode(string $line): ?string
    {
        if (preg_match('/"\s+(\d{3})\s+/', $line, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractDateFromLog(string $line): ?string
    {
        if (preg_match('/\[(\d{2}\/\w{3}\/\d{4}):/', $line, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Получает список файлов логов для анализа (текущий + история)
     */
    private function getLogFilesForRange(Carbon $endDate, int $daysBack = 2): array
    {
        $files = [];
        $baseLog = $this->logPath;

        if (file_exists($baseLog)) {
            $files[] = ['path' => $baseLog, 'compressed' => false];
        }

        for ($i = 1; $i <= $daysBack + 5; $i++) {
            $gzPath = "{$baseLog}.{$i}.gz";
            $plainPath = "{$baseLog}.{$i}";

            if (file_exists($gzPath)) {
                $files[] = ['path' => $gzPath, 'compressed' => true];
            } elseif (file_exists($plainPath)) {
                $files[] = ['path' => $plainPath, 'compressed' => false];
            }
        }

        return $files;
    }

    /**
     * Парсит логи и собирает расширенную статистику
     */
    private function parseLogs(string $targetDatePattern, Carbon $targetDate): array
    {
        $logFiles = $this->getLogFilesForRange($targetDate, 3);

        if (empty($logFiles)) {
            $this->warn("⚠️ Не найдено файлов логов для анализа");
            return $this->emptyStats($targetDatePattern);
        }

        $targetIps = [];
        $targetBotCount = 0;
        $targetRequestsByHour = [];
        $targetStatusCodes = [];
        $targetTotalRequests = 0;
        $targetTotalBytes = 0;

        $ipHistory = [];

        $historyDates = [];
        for ($i = 0; $i < 3; $i++) {
            $historyDates[] = $targetDate->copy()->subDays($i)->format($this->logFormatDate);
        }

        foreach ($logFiles as $fileInfo) {
            $file = $fileInfo['path'];
            $useZgrep = $fileInfo['compressed'];
            $grepCmd = $useZgrep ? 'zgrep' : 'grep';

            $datePatternForGrep = implode('|', array_map(fn($d) => preg_quote($d, '/'), $historyDates));

            $cmd = sprintf(
                "%s -hE '%s' %s 2>/dev/null",
                $grepCmd,
                $datePatternForGrep,
                escapeshellarg($file)
            );

            $output = shell_exec($cmd);
            if (!$output) continue;

            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                if (!preg_match('/^([\d\.a-f:]+)\s+-\s+-\s+\[/', $line, $m)) {
                    continue;
                }

                // 🎯 НОВАЯ ФИЛЬТРАЦИЯ: Оставляем только нужные эндпоинты
                // Извлекаем URI из первого quoted-блока (запроса)
                if (!preg_match('/^[^\"]*\"[A-Za-z]+\s+([^\s]+)\s+HTTP\/[\d\.]+\"/', $line, $reqMatch)) {
                    continue;
                }
                
                // Убираем query-параметры (?ref=1 и т.д.) для чистого сравнения путей
                $requestPath = parse_url($reqMatch[1], PHP_URL_PATH) ?: $reqMatch[1];
                
                // Проверяем, что путь начинается с /post/, /about (или /about/) или является строго /
                if (!preg_match('#^(/post/|/home(/|$)|/$)#', $requestPath)) {
                    continue;
                }
                // 🎯 КОНЕЦ ФИЛЬТРАЦИИ

                $logDate = $this->extractDateFromLog($line);
                if (!$logDate || !in_array($logDate, $historyDates)) {
                    continue;
                }

                $ip = $m[1];
                $isTargetDay = ($logDate === $targetDatePattern);

                $bytes = 0;
                if (preg_match('/"\s+\d{3}\s+(\d+|-)/', $line, $bm)) {
                    $bytes = ($bm[1] === '-') ? 0 : (int) $bm[1];
                }

                if ($isTargetDay) {
                    $targetTotalRequests++;
                    $targetTotalBytes += $bytes;

                    $hour = $this->extractHour($line);
                    if ($hour) {
                        $targetRequestsByHour[$hour] = ($targetRequestsByHour[$hour] ?? 0) + 1;
                    }

                    $status = $this->extractStatusCode($line);
                    if ($status) {
                        $targetStatusCodes[$status] = ($targetStatusCodes[$status] ?? 0) + 1;
                    }
                }

                if ($this->isBotIp($ip)) {
                    if ($isTargetDay) $targetBotCount++;
                    continue;
                }

                $userAgent = $this->extractUserAgent($line);
                if ($userAgent && $this->isBot($userAgent)) {
                    if ($isTargetDay) $targetBotCount++;
                    continue;
                }

                if (!isset($ipHistory[$ip])) {
                    $ipHistory[$ip] = [];
                }
                if (!isset($ipHistory[$ip][$logDate])) {
                    $ipHistory[$ip][$logDate] = 0;
                }
                $ipHistory[$ip][$logDate]++;

                if ($isTargetDay) {
                    $targetIps[] = $ip;
                }
            }
        }

        if (empty($targetIps) && $targetBotCount === 0) {
            return $this->emptyStats($targetDatePattern);
        }

        $ipCounts = array_count_values($targetIps);
        arsort($ipCounts);
        $totalHuman = array_sum($ipCounts);
        $uniqueCount = count($ipCounts);

        $persistentVisitors = $this->analyzePersistentVisitors($ipHistory, $historyDates);
        $suspiciousPatterns = $this->detectSuspiciousPatterns($ipHistory, $targetDatePattern);

        $peakHour = !empty($targetRequestsByHour) ?
            collect($targetRequestsByHour)->sortDesc()->keys()->first() : null;
        $peakHourCount = $peakHour ? $targetRequestsByHour[$peakHour] : 0;

        $statusSummary = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0];
        foreach ($targetStatusCodes as $code => $count) {
            $group = substr($code, 0, 1) . 'xx';
            $statusSummary[$group] = ($statusSummary[$group] ?? 0) + $count;
        }

        $trafficMB = round($targetTotalBytes / 1024 / 1024, 2);
        $avgPerUser = $uniqueCount > 0 ? round($totalHuman / $uniqueCount, 1) : 0;
        $rpm = $peakHourCount > 0 ? round($peakHourCount / 60, 1) : 0;

        return [
            'total_requests' => $totalHuman,
            'total_all' => $targetTotalRequests,
            'unique_ips' => $uniqueCount,
            'bots_filtered' => $targetBotCount,
            'traffic_mb' => $trafficMB,
            'rpm_peak' => $rpm,
            'top_visitors' => collect($ipCounts)->take(15)->map(function ($count, $ip) {
                $hostname = @gethostbyaddr($ip);
                return [
                    'ip' => $ip,
                    'count' => $count,
                    'host' => ($hostname !== $ip && $hostname) ? $hostname : null,
                ];
            })->values()->toArray(),
            'persistent_visitors' => $persistentVisitors,
            'suspicious_patterns' => $suspiciousPatterns,
            'peak_hour' => $peakHour,
            'peak_hour_count' => $peakHourCount,
            'status_codes' => $statusSummary,
            'avg_per_user' => $avgPerUser,
            'date' => $targetDatePattern,
        ];
    }

    private function analyzePersistentVisitors(array $ipHistory, array $historyDates): array
    {
        $persistent = [];

        foreach ($ipHistory as $ip => $daysData) {
            $activeDays = count($daysData);
            $totalRequests = array_sum($daysData);
            $avgPerDay = $activeDays > 0 ? round($totalRequests / $activeDays, 1) : 0;

            if (
                $activeDays >= $this->persistentDaysThreshold &&
                $avgPerDay >= $this->persistentMinRequests
            ) {
                $hostname = @gethostbyaddr($ip);

                $persistent[] = [
                    'ip' => $ip,
                    'host' => ($hostname !== $ip && $hostname) ? $hostname : null,
                    'active_days' => $activeDays,
                    'total_requests' => $totalRequests,
                    'avg_per_day' => $avgPerDay,
                    'daily_breakdown' => collect($daysData)
                        ->sortBy(fn($v, $k) => Carbon::createFromFormat($this->logFormatDate, $k)->timestamp)
                        ->take(7)
                        ->mapWithKeys(fn($count, $date) => [$date => $count])
                        ->toArray(),
                    'consecutive' => $this->countConsecutiveDays($daysData, $historyDates),
                ];
            }
        }

        usort($persistent, fn($a, $b) => $b['total_requests'] <=> $a['total_requests']);

        return array_slice($persistent, 0, 10);
    }

    private function countConsecutiveDays(array $daysData, array $allDates): int
    {
        $sortedDates = collect($allDates)
            ->filter(fn($d) => isset($daysData[$d]))
            ->sortBy(fn($d) => Carbon::createFromFormat($this->logFormatDate, $d)->timestamp)
            ->values()
            ->toArray();

        if (empty($sortedDates)) return 0;

        $maxConsecutive = 1;
        $current = 1;

        for ($i = 1; $i < count($sortedDates); $i++) {
            $prev = Carbon::createFromFormat($this->logFormatDate, $sortedDates[$i - 1]);
            $curr = Carbon::createFromFormat($this->logFormatDate, $sortedDates[$i]);

            if ($prev->diffInDays($curr) === 1) {
                $current++;
                $maxConsecutive = max($maxConsecutive, $current);
            } else {
                $current = 1;
            }
        }

        return $maxConsecutive;
    }

    private function detectSuspiciousPatterns(array $ipHistory, string $targetDate): array
    {
        $suspicious = [];

        foreach ($ipHistory as $ip => $daysData) {
            $flags = [];

            $avgPerDay = array_sum($daysData) / count($daysData);
            if ($avgPerDay > 500) {
                $flags[] = 'высокая активность (' . round($avgPerDay) . ' запросов/день)';
            }

            if (count($daysData) >= 5) {
                $requestsPerDay = collect($daysData)->values();
                $stdDev = $requestsPerDay->isEmpty() ? 0 :
                    sqrt($requestsPerDay->sum(fn($v) => pow($v - $avgPerDay, 2)) / $requestsPerDay->count());

                if ($stdDev < $avgPerDay * 0.3 && $avgPerDay > 30) {
                    $flags[] = 'подозрительно регулярная активность';
                }
            }

            $hostname = @gethostbyaddr($ip);
            $cloudProviders = ['amazonaws', 'googleusercontent', 'azure', 'digitalocean', 'ovh', 'hetzner'];
            foreach ($cloudProviders as $provider) {
                if ($hostname && str_contains(strtolower($hostname), $provider)) {
                    $flags[] = "облачный хостинг ($provider)";
                    break;
                }
            }

            if (!empty($flags) && (isset($daysData[$targetDate]) && $daysData[$targetDate] >= 20)) {
                $suspicious[] = [
                    'ip' => $ip,
                    'host' => ($hostname !== $ip && $hostname) ? $hostname : null,
                    'flags' => $flags,
                    'requests_today' => $daysData[$targetDate] ?? 0,
                ];
            }
        }

        return array_slice($suspicious, 0, 8);
    }

    private function emptyStats(string $datePattern): array
    {
        return [
            'total_requests' => 0, 'total_all' => 0, 'unique_ips' => 0, 'bots_filtered' => 0,
            'traffic_mb' => 0, 'rpm_peak' => 0, 'top_visitors' => [], 'persistent_visitors' => [],
            'suspicious_patterns' => [], 'peak_hour' => null, 'peak_hour_count' => 0,
            'status_codes' => [], 'avg_per_user' => 0, 'date' => $datePattern,
        ];
    }

    private function sendReport(array $stats, Carbon $date): void
    {
        $bot = new WorkerBot(
            env('NEXTCLOUD_DOMAIN'),
            env('NEXTCLOUD_WORKERBOT_NAME'),
            env('NEXTCLOUD_WORKERBOT_TOKEN')
        );

        $topList = collect($stats['top_visitors'])->map(function ($v, $i) {
            $hostInfo = $v['host'] ? " <i>({$v['host']})</i>" : '';
            return ($i + 1) . ". <code>{$v['ip']}</code>{$hostInfo} — {$v['count']}";
        })->implode("\n");

        $persistentBlock = '';
        if (!empty($stats['persistent_visitors'])) {
            $persistentBlock = "🔄 <b>Персистентные посетители ({$this->persistentDaysThreshold}+ дней):</b>\n";
            foreach ($stats['persistent_visitors'] as $i => $pv) {
                $hostInfo = $pv['host'] ? " <i>({$pv['host']})</i>" : '';
                $consecutive = $pv['consecutive'] >= 3 ? " 🔗{$pv['consecutive']}дн. подряд" : '';
                $daily = collect($pv['daily_breakdown'])
                    ->map(fn($c, $d) => "$d:$c")
                    ->implode(' ');

                $persistentBlock .= ($i + 1) . ". <code>{$pv['ip']}</code>{$hostInfo} — "
                    . "{$pv['total_requests']} всего ({$pv['avg_per_day']}/день, {$pv['active_days']}дн.){$consecutive}\n"
                    . "   <i>$daily</i>\n";
            }
            $persistentBlock .= "\n";
        }

        $suspiciousBlock = '';
        if (!empty($stats['suspicious_patterns'])) {
            $suspiciousBlock = "🚩 <b>Подозрительная активность:</b>\n";
            foreach ($stats['suspicious_patterns'] as $i => $sp) {
                $hostInfo = $sp['host'] ? " <i>({$sp['host']})</i>" : '';
                $flags = implode(', ', $sp['flags']);
                $suspiciousBlock .= ($i + 1) . ". <code>{$sp['ip']}</code>{$hostInfo} — {$sp['requests_today']} запросов сегодня\n"
                    . "   <i>🏷️ $flags</i>\n";
            }
            $suspiciousBlock .= "\n";
        }

        $extraStats = '';
        if ($stats['peak_hour']) {
            $extraStats .= "⏰ <b>Пик:</b> {$stats['peak_hour']}:00 ({$stats['peak_hour_count']} запросов, ~{$stats['rpm_peak']}/мин)\n";
        }
        if (!empty($stats['status_codes'])) {
            $statuses = collect($stats['status_codes'])
                ->filter(fn($v) => $v > 0)
                ->map(fn($v, $k) => "$k: $v")
                ->implode(', ');
            if ($statuses) $extraStats .= "🔢 <b>Статусы:</b> $statuses\n";
        }
        if ($stats['avg_per_user'] > 0) {
            $extraStats .= "📐 <b>Среднее:</b> {$stats['avg_per_user']} запросов/пользователя\n";
        }
        if ($stats['traffic_mb'] > 0) {
            $extraStats .= "📦 <b>Трафик:</b> {$stats['traffic_mb']} МБ\n";
        }

        $botBlock = '';
        if ($stats['bots_filtered'] > 0) {
            $botPercent = $stats['total_all'] > 0 ? round($stats['bots_filtered'] / $stats['total_all'] * 100) : 0;
            $botBlock = "🤖 <b>Отфильтровано ботов:</b> {$stats['bots_filtered']} ($botPercent%)\n";
        }

        $trafficBlock = '';
        if ($stats['total_all'] > $stats['total_requests']) {
            $trafficBlock = "📈 <b>Всего запросов:</b> {$stats['total_all']}\n";
        }

        $text = "📈 <b>Отчёт за {$date->format('d.m.Y')}</b>\n\n"
            . "👥 <b>Уникальные посетители:</b> {$stats['unique_ips']}\n"
            . "🔄 <b>Запросов (люди):</b> {$stats['total_requests']}\n"
            . $trafficBlock
            . $botBlock
            . "\n"
            . $extraStats
            . "\n"
            . $persistentBlock
            . $suspiciousBlock
            . "🏆 <b>Топ-15 активных IP:</b>\n"
            . ($topList ?: "   — нет данных —") . "\n\n"
            . "🕒 Сформирован: " . now()->timezone('Europe/Moscow')->format('H:i:s');

        $messages = $this->splitMessage($text, 4000);
        foreach ($messages as $msg) {
            $bot->sendMessage(env('NEXTCLOUD_ROOM_ID'), strip_tags($msg));
        }
    }

    private function splitMessage(string $text, int $maxLength): array
    {
        if (mb_strlen($text) <= $maxLength) {
            return [$text];
        }

        $messages = [];
        $lines = explode("\n", $text);
        $current = '';

        foreach ($lines as $line) {
            if (mb_strlen($current . "\n" . $line) > $maxLength) {
                if ($current) {
                    $messages[] = $current;
                    $current = '';
                }
                if (mb_strlen($line) > $maxLength) {
                    $parts = str_split($line, $maxLength - 10);
                    foreach ($parts as $i => $part) {
                        if ($i < count($parts) - 1) {
                            $messages[] = $part . '...';
                        } else {
                            $current = '...' . $part;
                        }
                    }
                } else {
                    $current = $line;
                }
            } else {
                $current .= ($current ? "\n" : '') . $line;
            }
        }

        if ($current) {
            $messages[] = $current;
        }

        return $messages;
    }
}