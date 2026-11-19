<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Nextcloud\WorkerBot;
use App\Models\Post;
use App\Models\ReactionElement;

class SendNextcloudReactionNotification implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public int $reactionId;
    public string $ip;
    public string $action;

    public function __construct(int $postId, int $reactionId, string $ip, string $action)
    {
        $this->postId = $postId;
        $this->reactionId = $reactionId;
        $this->ip = $ip;
        $this->action = $action;
    }

    public function handle(): void
    {
        try {
            $bot = new WorkerBot(
                env('NEXTCLOUD_DOMAIN'),
                env('NEXTCLOUD_WORKERBOT_NAME'),
                env('NEXTCLOUD_WORKERBOT_TOKEN')
            );

            // 📦 Загружаем данные из БД
            $post = Post::find($this->postId);
            $reaction = ReactionElement::find($this->reactionId);

            $postTitle = $post?->title ?? "Пост #{$this->postId}";
            $reactionName = $reaction?->name ?? "Реакция #{$this->reactionId}";

            // 🔗 Генерируем правильный URL (с подстраховкой)
            $postUrl = $this->buildPostUrl($post);

            $emoji = $this->action === 'removed' ? '🗑️' : '✅';
            $emojiText = $this->action === 'removed' ? 'Убрана' : 'Добавлена';

            // 📝 Формируем текст уведомления
            $text = "{$emoji} Реакция $emojiText\n\n"
                . "📰 Пост: {$postTitle}\n"
                . "🎭 Реакция: {$reactionName}\n"
                . "🌐 IP: {$this->ip}\n"
                . "🕒 Время: " . now()->timezone('Europe/Moscow')->format('d.m.Y H:i:s');

            // 🔘 Одна кнопка "Открыть пост"
            $replyMarkup = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '👁️ Открыть пост', 'url' => $postUrl],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE);

            $bot->sendMessage(
                
            env('NEXTCLOUD_ROOM_ID'),$text);


        } catch (\Throwable $e) {
            Log::error('Telegram queue job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job?->uuid(),
                'post_id' => $this->postId,
                'reaction_id' => $this->reactionId,
            ]);

            $this->release(10);
        }
    }

    /**
     * 🔗 Строим правильный URL для поста
     */
    protected function buildPostUrl(?Post $post): string
    {
        // 🎯 Явный домен из конфига (на случай если APP_URL не подгрузился)
        $domain = config('app.url' );

        if ($post?->slug) {
            return "{$domain}/post/{$post->slug}";
        }

        // Fallback на ID если нет slug
        return "{$domain}/post/{$this->postId}";
    }

    public function tries(): int
    {
        return 3;
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }
}