<?php

namespace App\Filament\Admin\Resources\ReactionPacks\Pages;

use App\Filament\Admin\Resources\ReactionPacks\ReactionPackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReactionPacks extends ListRecords
{
    protected static string $resource = ReactionPackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
