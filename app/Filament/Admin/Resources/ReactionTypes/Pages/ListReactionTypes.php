<?php

namespace App\Filament\Admin\Resources\ReactionTypes\Pages;

use App\Filament\Admin\Resources\ReactionTypes\ReactionTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReactionTypes extends ListRecords
{
    protected static string $resource = ReactionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
