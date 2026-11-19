<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\StatsOverview; // Импортируем наш новый виджет
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/'; // Путь к дашборду
    protected static ?string $navigationLabel = 'Статистика';
    protected static ?string $modelLabel = 'Статистика';
    protected static ?string $pluralModelLabel = 'Статистика';
    protected static ?string $title = 'Статистика';

    // Метод для указания виджетов на дашборде
    public function getWidgets(): array
    {
        return [
            // Встроенные виджеты
            // \Filament\Widgets\AccountWidget::class, // Уже добавлен в PanelProvider
            // \Filament\Widgets\FilamentInfoWidget::class, // Уже добавлен в PanelProvider

            // Наш кастомный виджет
            StatsOverview::class,
            // Вы можете добавить сюда и другие виджеты
            // \App\Filament\Admin\Widgets\AnotherWidget::class,
        ];
    }

    // Опционально: настройка сетки виджетов (колонки и т.д.)
    // protected function getColumns(): int | string | array
    // {
    //     return 3; // Например, 3 колонки
    // }
}