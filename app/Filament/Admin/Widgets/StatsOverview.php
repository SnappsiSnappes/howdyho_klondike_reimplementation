<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\PostView;
use App\Models\ReactionElement;
use App\Models\ReactionPack;
use App\Models\ReactionType;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Получаем данные из БД
        $usersCount = User::count();
        $postsCount = Post::count();
        $commentsCount = Comment::count();
        $reactionsCount = PostReaction::count();
        $viewsCount = PostView::count();
        $elementsCount = ReactionElement::count();
        $packsCount = ReactionPack::count();
        $typesCount = ReactionType::count();

        return [
            Stat::make('Пользователи', $usersCount)
                ->description('Зарегистрировано')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success') // Зелёный
                ->chart([78, 85, 60, 75, 90, 55, 65]), // Пример данных для графика (опционально)

            Stat::make('Посты', $postsCount)
                ->description('Опубликовано')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary') // Синий
                ->chart([40, 55, 70, 50, 65, 80, 75]), // Пример данных

            Stat::make('Комментарии', $commentsCount)
                ->description('Оставлено')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('warning') // Жёлтый
                ->chart([20, 35, 30, 45, 50, 60, 55]), // Пример данных

            Stat::make('Реакции', $reactionsCount)
                ->description('Поставлено реакций под постами')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger') // Красный
                ->chart([10, 25, 20, 40, 35, 50, 45]), // Пример данных

            Stat::make('Просмотры', $viewsCount)
                ->description('Всего')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info') // Информационный (обычно синий)
                ->chart([100, 120, 90, 140, 160, 110, 130]), // Пример данных

            Stat::make('Элементы', $elementsCount)
                ->description('В паках')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('gray') // Серый
                ->chart([5, 15, 10, 25, 20, 30, 25]), // Пример данных
        ];
    }
}