<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class CommentController extends Controller
{

    public function getComments(Post $post, Request $request)
    {
        $offset = (int) $request->input('offset', 0);
        $limit = 3;

        // Один ключ на весь список одобренных комментариев
        $cacheKey = "post_comments_{$post->id}_all";

        // Получаем ВСЕ одобренные комментарии (отсортированы latest → новые сверху)
        $allComments = Cache::remember($cacheKey, now()->addDays(7), function () use ($post) {
            return $post->comments()
                ->where('approved', true)
                ->latest()
                ->get()
                ->map(function ($comment) {
                    $comment->humanReadableDate = $comment->created_at->diffForHumans();
                    return $comment;
                })
                ->values(); // 🔥 ВОТ ЭТО ВАЖНО: сбрасывает ключи → [0 => ..., 1 => ...]
        });

        // Режем на фронтенде — но если хочешь, можешь резать тут
        $paginated = $allComments->slice($offset, $limit);

        return response()->json([
            'comments' => $paginated,
            'offset' => $offset,
            'total_amount' => $allComments->count(),
        ]);
    }



public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|string|max:30',
        'text' => 'required|string|max:250',
        'post_id' => 'required|exists:post,id', 
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $ip = $request->ip();

    // 🔒 Если уже есть НЕОДОБРЕННЫЙ комментарий с этого IP — не создаём новый
    if (Comment::where('ip_address', $ip)
        ->where('approved', false)
        ->exists()
    ) {
        // Просто имитируем успех — как будто отправлено
        return response()->json([
            'message' => 'Комментарий отправлен на модерацию.',
        ]);
    }

    // ⏳ Также можно добавить ограничение "1 в сутки", но это уже другое правило
    // if (Comment::where('ip_address', $ip)->where('created_at', '>=', now()->subDay())->exists()) {
    //     return response()->json(['message' => 'Один коммент в сутки.'], 429);
    // }

    $comment = Comment::create([
        'username' => $request->username,
        'text' => $request->text,
        'post_id' => $request->post_id,
        'ip_address' => $ip,
        'approved' => false,
    ]);

    // Сбрасываем кэш (на случай, если вдруг approved=true, но у нас approved=false — не нужно, но безопасно)
    Cache::forget("post_comments_{$request->post_id}_all");

    return response()->json([
        'message' => 'Комментарий отправлен на модерацию.',
        'comment' => $comment,
    ]);
}
}
