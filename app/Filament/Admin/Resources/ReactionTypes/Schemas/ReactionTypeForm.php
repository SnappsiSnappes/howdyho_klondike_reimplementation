<?php

namespace App\Filament\Admin\Resources\ReactionTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReactionTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название типа')
                    ->required(),
                Toggle::make('sticker')
                ->label('Режим - стикер'),
            ]);
    }
}
