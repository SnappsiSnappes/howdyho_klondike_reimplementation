<?php

namespace App\Filament\Admin\Resources\ReactionTypes\Pages;

use App\Filament\Admin\Resources\ReactionTypes\ReactionTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReactionType extends EditRecord
{
    protected static string $resource = ReactionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
