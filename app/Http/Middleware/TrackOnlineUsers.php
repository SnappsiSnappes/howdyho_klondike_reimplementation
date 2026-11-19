<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackOnlineUsers
{
    public function handle(Request $request, Closure $next): Response
    {

        $redis = Redis::connection();


        // Для гостей используем IP-адрес
        // Laravel автоматически учитывает прокси (CloudFlare, Nginx и т.д.)
        $ip = $request->ip();

        // Опционально: добавляем User Agent для уникальности
        // (чтобы различать пользователей за одним NAT/прокси)
        $userAgent = $request->userAgent() ?? 'unknown';
        $member = 'ip:' . md5($ip . ':' . $userAgent);

        // Логируем для отладки (можно убрать в продакшене)
        Log::info('TrackOnlineUsers: ' . $member . ' | IP: ' . ($request->ip() ?? 'unknown'));

        // Добавляем в Redis с текущим timestamp
        $redis->zadd('online_users', time(), $member);

        // Удаляем старые записи (старше 2 минут)
        $redis->zremrangebyscore('online_users', 0, time() - 8);

        return $next($request);
    }
}