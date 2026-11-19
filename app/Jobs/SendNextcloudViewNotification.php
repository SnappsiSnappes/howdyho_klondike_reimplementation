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

class SendNextcloudViewNotification implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;
    public int $reactionId;
    public string $ip;
    public string $action;
    

    public function __construct(int $postId, string $ip)
    {
        $this->postId = $postId;
        $this->ip = $ip;
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
            $postTitle = $post?->title ?? "Пост #{$this->postId}";
            $postUrl = $this->buildPostUrl($post);


            // 📝 Формируем текст уведомления
            $text ="+1 просмотр на пост $postTitle " . now()->timezone('Europe/Moscow')->format('d.m.Y H:i:s');

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
        $domain = config('app.url');

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