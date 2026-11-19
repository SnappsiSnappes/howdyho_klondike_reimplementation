<?php

namespace App\Filament\Admin\Resources\ReactionPacks\Pages;

use App\Filament\Admin\Resources\ReactionPacks\ReactionPackResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReactionPack extends EditRecord
{
    protected static string $resource = ReactionPackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
