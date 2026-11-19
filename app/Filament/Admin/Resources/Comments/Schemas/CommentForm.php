<?php

namespace App\Filament\Admin\Resources\Comments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->label('Имя пользователя')
                    ->required(),
                Textarea::make('text')
                    ->label('Текст комментария')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('ip_address')
                    ->label('IP-адрес')

                    ->required(),
                Toggle::make('approved')
                    ->label('Одобрен')
                    ->required(),
                TextInput::make('post_id')
                    ->label('ID поста')

                    ->required()
                    ->numeric(),
            ]);
    }
}
