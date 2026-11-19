<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Jobs\SendNextcloudViewNotification;
use App\Jobs\SendTelegramReactionNotification;
use App\Jobs\SendNextcloudReactionNotification;
use App\Models\Post;
use App\Models\User; // Импортируем модель User
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use App\Models\PostReaction;
use App\Models\PostView;
use App\Models\ReactionElement;
use Telegram\Bot\Laravel\Facades\Telegram;
use function in_array;


class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // 🔔 Ваш Telegram ID (админ)


    public function index(Request $request)
    {
        // Базовый запрос
        $query = Post::with(['user', 'category']);

        // --- 1. Фильтрация по категории ---
        $category = null;
        $CategorySlug = $request->input('category'); // ⚠️ Убедись, что в JS ты передаёшь `category`, а не `CategorySlug`

        if ($CategorySlug) {
            $category = Category::where('slug', $CategorySlug)->firstOrFail();
            $categoryIds = $this->getAllCategoryIds($category);
            $query->whereIn('category_id', $categoryIds);
        } else {
            $category = [
                'name' => 'Свежие посты',
                'description' => 'добро пожаловать на мой личный блог!',
                'preview_img' => 'decoration/snappes.ico'
            ];
        }

        // --- 2. Поиск ---
        $searchTerm = $request->input('search');
        if ($searchTerm and !$CategorySlug) {
            $searchTerm = mb_substr(trim($searchTerm), 0, 15);

            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('body', 'like', "%{$searchTerm}%")
                    ->orWhere('preview_text', 'like', "%{$searchTerm}%");
            });
            $category = [
                'name' => 'Поиск',
                'description' => 'по запросу ' . '«' . $searchTerm . '»',
                'preview_img' => 'decoration/search.svg'
            ];
        }

        // --- 3. Сортировка ---
        $sortParam = $request->input('sort', 'date:desc');
        [$sort, $direction] = explode(':', $sortParam, 2);

        $allowedSorts = ['reactions', 'views', 'date'];
        $allowedDirections = ['asc', 'desc'];

        $sort = in_array($sort, $allowedSorts) ? $sort : 'date';
        $direction = in_array($direction, $allowedDirections) ? $direction : 'desc';

        if ($sort === 'reactions') {
            $query->withCount('reactions')->orderBy('reactions_count', $direction);
        } elseif ($sort === 'views') {
            $query->orderBy('views_count', $direction);
        } elseif ($sort === 'date') {
            $query->orderBy('created_at', $direction);
        }

        // --- 4. Пагинация + human date ---
        $posts = $query->paginate(5);

        $posts->each(function ($post) {
            $post->humanReadableDate = $post->created_at->diffForHumans();
        });

        // --- 5. Ответ ---
        return inertia('Post/Index', [
            'post' => $posts,
            'SelectedCategory' => $category,
            'search' => $searchTerm,
            'sort' => $sortParam,
        ]);
    }



    protected function getAllCategoryIds(Category $rootCategory): array
    {
        return Cache::remember("category_ids_tree_{$rootCategory->id}", now()->addHours(24), function () use ($rootCategory) {
            // Загружаем ВСЕ категории один раз
            $allCategories = Category::all(['id', 'parent_id']);

            // Строим map: parent_id -> [child_ids]
            $childrenMap = [];
            foreach ($allCategories as $cat) {
                $parentId = $cat->parent_id ?: 0; // корень — parent_id = null → 0
                $childrenMap[$parentId][] = $cat->id;
            }

            // Обходим дерево от корня (DFS)
            $result = [];
            $stack = [$rootCategory->id];

            while (!empty($stack)) {
                $currentId = array_pop($stack);
                $result[] = $currentId;

                if (!empty($childrenMap[$currentId])) {
                    $stack = array_merge($stack, $childrenMap[$currentId]);
                }
            }

            return $result;
        });
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('message', 'Пожалуйста, авторизуйтесь, чтобы создать пост.');
        }

        return inertia('Post/Create', [
            'categories' => Category::buildTreeWithPaths(),
        ]);
    }



    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        // Обработка preview_image
        if ($request->hasFile('preview_image')) {
            $file = $request->file('preview_image');

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());

            // Очищаем имя от спецсимволов
            $safeName = Str::slug($originalName, '-', 'utf8') . '_' . time() . '.' . $extension;
            $filename = $safeName . '.' . $extension;
            $directory = 'images';
            $path = $directory . '/' . $filename;

            // Проверяем, существует ли файл — если да, генерируем уникальное имя
            $counter = 1;
            while (Storage::disk('public')->exists($path)) {
                $newFilename = $safeName . '(' . $counter . ').' . $extension;
                $path = $directory . '/' . $newFilename;
                $counter++;
            }

            // Сохраняем файл
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

            // Устанавливаем путь в validated данные
            $validated['preview_image'] = '/storage/' . $path;
        }

        // Создаём пост
        $post = Post::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'preview_text' => $validated['preview_text'],
            'preview_image' => $validated['preview_image'] ?? null,
            'category_id' => $validated['category_id'],
        ]);

        return redirect()->route('post.show', $post)
            ->with('message', ['message' => 'Пост создан', 'type' => 'success']);
    }


    /**
     * Display the specified resource.
     */
    public function show(Post $post, Request $request)
    {

        $ip = $request->ip();
        // Проверяем, не смотрел ли этот IP уже пост
        $alreadyViewed = PostView::where('post_id', $post->id)
            ->where('ip_address', $ip)
            ->exists();

        if (!$alreadyViewed) {
            // Записываем просмотр
            PostView::create([
                'post_id' => $post->id,
                'ip_address' => $ip
            ]);

            // Увеличиваем счётчик
            $post->increment('views_count');
            $this->notifyAdminView($post->id,$ip);
        }

        // Загружаем отношения
        $post->load(['user', 'category']);

        // Форматируем дату
        $post->humanReadableDate = $post->created_at->diffForHumans();

        // --- Получаем 3 похожих поста из той же категории ---
        $relatedPosts = Post::with(['user', 'category'])
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id) // Исключаем текущий пост
            ->latest()
            ->limit(3)

            ->get()->each(function ($relatedPost) {
                $relatedPost->humanReadableDate = $relatedPost->created_at->diffForHumans();
            });

        return inertia('Post/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts, // <--- Передаем сюда
            'authUser' => auth()->user(),
            'IsAdmin' => auth()->user()?->can('IsAdmin', User::class),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }


        // Return the post data to the frontend
        return inertia('Post/Edit', [
            'post' => $post,
            'categories' => Category::buildTreeWithPaths(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $validated = $request->validated();

        // Обработка preview_image
        if ($request->hasFile('preview_image')) {
            // Удаляем старое изображение
            if ($post->preview_image) {
                $oldPath = str_replace('/storage/', '', $post->preview_image);
                Storage::disk('public')->delete($oldPath);
            }

            // Сохраняем новое
            $file = $request->file('preview_image');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());

            $filename = Str::slug($originalName, '-', 'utf8') . '_' . time() . '.' . $extension;
            $path = 'images/' . $filename;

            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
            $validated['preview_image'] = '/storage/' . $path;
        } elseif ($request->boolean('delete_preview_image')) {
            // Удаляем изображение по запросу
            if ($post->preview_image) {
                $oldPath = str_replace('/storage/', '', $post->preview_image);
                Storage::disk('public')->delete($oldPath);
            }
            $validated['preview_image'] = null;
        } else {
            // Сохраняем текущее изображение
            $validated['preview_image'] = $post->preview_image;
        }

        $post->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'preview_text' => $validated['preview_text'],
            'preview_image' => $validated['preview_image'],
            'category_id' => $validated['category_id'],
        ]);

        return redirect()->route('post.show', $post)
            ->with('message', ['message' => 'Пост обновлён', 'type' => 'success']);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Удаление поста
        $post->delete();

        // Перенаправление с сообщением об успехе
        return redirect()->route('post.index')->with('message', ['message' => 'Пост удалён', 'type' => 'success']);
    }


    public function ChangeReaction(Request $request, $postId)
    {
        $ip = $request->ip();
        $reactionId = $request->input('reactionId');

        if (!$reactionId) {
            return response()->json(['message' => 'reactionId required'], 400);
        }

        $post = Post::findOrFail($postId);

        // Проверяем, ставил ли этот IP именно эту реакцию
        $existingReaction = $post->reactions()
            ->where('reaction_element_id', $reactionId)
            ->where('ip_address', $ip)
            ->first();

        $action = $existingReaction ? 'removed' : 'added';

        if ($existingReaction) {
            // Удаляем — всегда разрешено
            $existingReaction->delete();
            $this->notifyAdminReaction($postId, $reactionId, $ip, $action);

            return response()->json(['removed' => true]);
        }


        // Получаем все уникальные reaction_element_id, уже используемые в посте
        $usedReactionElementIds = $post->reactions()
            ->distinct('reaction_element_id')
            ->pluck('reaction_element_id')
            ->toArray();

        $isReactionTypeAlreadyUsed = in_array($reactionId, $usedReactionElementIds);
        $uniqueCount = count($usedReactionElementIds);

        $ReactionLimint = 56;
        // Если реакция нового типа, и лимит исчерпан — запрещаем
        if (!$isReactionTypeAlreadyUsed && $uniqueCount >= $ReactionLimint) {
            return response()->json([
                'message' => "Нельзя добавить новую реакцию, достигнут лимит $ReactionLimint реакций на пост.",
                'removed' => false,
            ], 403);
        }

        // Иначе — разрешаем добавить
        PostReaction::create([
            'post_id' => $postId,
            'reaction_element_id' => $reactionId,
            'ip_address' => $ip,
        ]);

        // 🚀 Отправляем уведомление админу (неблокирующее)
        $this->notifyAdminReaction($postId, $reactionId, $ip, $action);


        return response()->json(['removed' => false]);
    }
    public function getReactions(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $allReactions = $post->reactions()->get([
            'reaction_element_id',
            'ip_address'
        ]);
        // ID всех элементов реакций (уникальные)
        $reactionElementIds = $allReactions->pluck('reaction_element_id')->unique()->toArray();
        // Если нет реакций — возвращаем пустой ответ
        if (empty($reactionElementIds)) {
            return response()->json([
                'reactions' => [],
                'user_reacted_to' => [],
            ]);
        }
        // Загружаем элементы реакций с типами (один запрос)
        $elements = ReactionElement::with('type:id,name,sticker')
            ->whereIn('id', $reactionElementIds)
            ->select('id', 'name', 'file_name', 'type_id')
            ->get()
            ->keyBy('id');
        // Группируем и считаем
        $reactionCounts = $allReactions
            ->groupBy('reaction_element_id')
            ->map(function ($group, $elementId) use ($elements) {
                return [
                    'count' => $group->count(),
                    'element' => $elements->get($elementId)
                ];
            })
            ->filter()
            ->toArray();
        // Текущий пользователь (по IP)
        $ip = $request->ip();
        $userReactionIds = $allReactions
            ->where('ip_address', $ip)
            ->pluck('reaction_element_id')
            ->map(fn($id) => (string) $id)
            ->toArray();
        return response()->json([
            'reactions' => $reactionCounts,
            'user_reacted_to' => $userReactionIds,
        ]);
    }

    /**
     * 🔔 Отправляет уведомление о смене реакции
     */
    protected function notifyAdminReaction(int $postId, int $reactionId, string $ip, string $action): void
    {
        // 🚀 Отправляем задачу в очередь
        // SendTelegramReactionNotification::dispatch(
        //     $postId,
        //     $reactionId,
        //     $ip,
        //     $action,
        // )->onQueue('default'); // 👈 Можно создать отдельную очередь 'telegram'

                // 🚀 Отправляем задачу в очередь
        SendNextcloudReactionNotification::dispatch(
            $postId,
            $reactionId,
            $ip,
            $action,
        )->onQueue('default'); // 👈 Можно создать отдельную очередь 'telegram'
    }

        protected function notifyAdminView(int $postId, string $ip,): void
    {
        SendNextcloudViewNotification::dispatch(
            $postId,
            $ip,
        )->onQueue('default'); // 👈 Можно создать отдельную очередь 'telegram'
    }
}
