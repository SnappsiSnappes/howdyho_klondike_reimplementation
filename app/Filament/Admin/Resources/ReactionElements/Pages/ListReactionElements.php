<?php

namespace App\Filament\Admin\Resources\ReactionElements\Pages;

use App\Filament\Admin\Resources\ReactionElements\ReactionElementResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReactionElements extends ListRecords
{
    protected static string $resource = ReactionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('bulk_import')
                ->label('Массовый импорт')
                ->url('/reactions/upload-pack')
                ->icon('heroicon-o-arrow-up-tray')
                ->openUrlInNewTab(), // опционально: открыть в новой вкладке
        ];
    }
}