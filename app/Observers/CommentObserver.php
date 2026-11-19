<?php

namespace App\Observers;

use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

class CommentObserver
{
    /**
     * Срабатывает при любом сохранении (create/update)
     */
    public function saved(Comment $comment): void
    {
        $this->clearPostCommentsCache($comment->post_id);
    }

    /**
     * Срабатывает при удалении
     */
    public function deleted(Comment $comment): void
    {
        $this->clearPostCommentsCache($comment->post_id);
    }

    /**
     * Срабатывает при восстановлении (если используешь soft deletes)
     */
    public function restored(Comment $comment): void
    {
        $this->clearPostCommentsCache($comment->post_id);
    }

    /**
     * Удаляет кэш комментариев для поста
     */
    private function clearPostCommentsCache(int $postId): void
    {
        Cache::forget("post_comments_{$postId}_all");
    }
}