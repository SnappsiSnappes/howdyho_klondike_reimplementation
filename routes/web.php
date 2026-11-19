<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ReactionsController;
use App\Models\Category;
use App\Models\ReactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Middleware\RestrictAuthRoutes;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostReaction;
use App\Models\PostView;
// Гости
Route::middleware(['guest', RestrictAuthRoutes::class])->group(function () {

    //     Регистрация не трогать
    Route::inertia('/register', 'Auth/Register')->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Вход
    Route::inertia('/login', 'Auth/Login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login_post');



});

// Аутентифицированные пользователи
Route::middleware('auth')->group(function () {

    // Главная страница (dashboard)
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Route::get('/dashboard', [PostController::class, 'index'])->name('dashboard');

    // Выход
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout_get');


    // Редактирование профиля
    // Route::inertia('/edit', 'Auth/Edit')->name('edit');
    // Route::post('/edit', [AuthController::class, 'edit']);

    // Удаление пользователя
    // Route::post('/delete_user', [AuthController::class, 'delete_user'])->name('delete_user');

    // Получение данных пользователя для /edit
    Route::get('/user', function () {
        return request()->user()->only('id', 'name', 'avatar', 'email');
    });


    // Картинки в постах
    Route::post('/upload-image', [ApiController::class, 'StoreImage']);
    Route::post('/delete-images', [ApiController::class, 'deleteImages']);

    // Файлы в постах
    Route::post('/upload-file', [ApiController::class, 'StoreFile']);




    // Массовое добавление реакций
    Route::get('/reactions/upload-pack', function () {
        return inertia('Reactions/UploadPack');
    })->name('reactions.upload-pack.form');

    Route::post('/reactions/upload-pack', [ReactionsController::class, 'uploadPack'])
        ->name('reactions.upload-pack');

    Route::get('/api/reaction-packs', [ReactionsController::class, 'getReactionPacks'])
        ->name('api.reaction-packs');

    Route::get('/api/reaction-types', [ReactionsController::class, 'getReactionTypes'])
        ->name('api.reaction-types');

});



Route::post('/SearchReaction', [ReactionsController::class, 'SearchReaction'])->name('SearchReaction');

Route::post('/post/{postId}/react', [PostController::class, 'ChangeReaction'])->name('post.react');

Route::get('/GetReactions', [ReactionsController::class, 'GetAllReactions'])->name('GetAllReactions');

Route::get('/GetCertainPack/{packId}', [ReactionsController::class, 'GetCertainPack'])->name('GetCertainPack');


// получить реакции на пост
Route::get('/post/{postId}/reactions', [PostController::class, 'getReactions'])->name('post.reactions');


// Главная страница
Route::get('/', [PostController::class, 'index'])->name('home');
// О нас
// Route::inertia('/about', 'About')->name('about');


// Ресурсные маршруты с переопределением update
Route::resource('post', PostController::class)->except(['update']);


// Добавляем отдельный маршрут для обновления через POST
Route::post('/post/{post}', [PostController::class, 'update'])->name('post.update');

// комментарии
Route::get('/post/{post}/comments', [CommentController::class, 'getComments']);
Route::post('/comment', [CommentController::class, 'store']);

// ВСЕ несуществующие маршруты — отдаём SPA-приложение
Route::fallback(function () {
    return inertia('404'); // Обрати внимание: заглавные буквы, слэш
})->name('spa.fallback');


// 🤖 Telegram Webhook (должен быть после SPA fallback, если используете Inertia)
Route::post('/telegram/webhook', function () {
    $telegram = Telegram::bot(env('TELEGRAM_BOT_ID'));

    // 🎯 Обрабатываем команду через встроенный хендлер
    $update = $telegram->commandsHandler(true); // true = webhook mode

    // ✅ Возвращаем 200 для Telegram
    return response('', 200);
})->withoutMiddleware(['web', 'api', 'auth']); // 🔥 Важно: отключаем стандартные мидлвары!


Route::get('/get-stats', function () {
    // Кэшируем все данные на 5 секунд
    $stats = Cache::remember('all_stats', 5, function () {
        return [
            'posts' => Post::count(),
            'comments' => Comment::count(),
            'reactions' => PostReaction::count(),
            'views' => PostView::count(),
            'online' => Redis::connection()->zcard('online_users'),
        ];
    });
    
    return response()->json($stats);
});



