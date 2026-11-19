<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Inertia\Middleware;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\PostView;
// ... остальные импорты

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        // 1. Кэшируем категории
        $categories = Cache::remember('categories_tree', now()->addHours(24), function () {
            $all = Category::all(['id', 'name', 'parent_id', 'preview_img', 'slug', 'collection_img']);

            // ИСПРАВЛЕНИЕ 1: Ищем корневые категории. 
            // whereIn учтет и null, и 0, чтобы точно не вернуть пустоту.
            $root = $all->whereIn('parent_id', [0, null]);

            // ИСПРАВЛЕНИЕ 2 (Оптимизация): Группируем по parent_id.
            // Это позволит находить детей за O(1) вместо перебора всей коллекции.
            $grouped = $all->groupBy('parent_id');

            $buildTree = function ($items) use (&$buildTree, $grouped) {
                return $items->map(function ($item) use ($buildTree, $grouped) {
                    // Мгновенно получаем детей из сгруппированной коллекции
                    $children = $grouped->get($item->id, collect());

                    // ИСПРАВЛЕНИЕ 3: СРАЗУ переводим модель в массив.
                    // Кэшировать "сырые" объекты Eloquent в Redis опасно (проблемы с сериализацией).
                    // Inertia отлично понимает обычные PHP-массивы.
                    $array = $item->toArray();

                    if ($children->isNotEmpty()) {
                        $array['children'] = $buildTree($children);
                    }

                    return $array;
                })->all(); // ->all() вернет обычный array вместо Collection
            };

            return $buildTree($root);
        });

        // 2. Кэшируем статистику
        // ИСПРАВЛЕНИЕ 4: now()->addMinutes(5) вместо просто 5 (иначе это 5 секунд)
        $stats = Cache::remember('dashboard_stats', now()->addSeconds(8), function () {
            return [
                'posts' => Post::count(),
                'comments' => Comment::count(),
                'reactions' => PostReaction::count(),
                'views' => PostView::count(),
                'online' => Redis::connection()->zcard('online_users'),
            ];
        });

        return array_merge(parent::share($request), [
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
                'type' => fn() => $request->session()->get('flash_type', 'info'),
            ],
            // 'csrf_token' => csrf_token(),
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }
}