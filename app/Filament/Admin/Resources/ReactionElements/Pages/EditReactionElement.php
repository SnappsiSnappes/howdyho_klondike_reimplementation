<?php

namespace App\Filament\Admin\Resources\ReactionElements\Pages;

use App\Filament\Admin\Resources\ReactionElements\ReactionElementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReactionElement extends EditRecord
{
    protected static string $resource = ReactionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
