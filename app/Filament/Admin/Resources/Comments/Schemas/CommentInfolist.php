<?php

namespace App\Filament\Admin\Resources\Comments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('username')
                    ->label('Имя пользователя')
                ,
                
                TextEntry::make('text')
                    ->label('Текст комментария')

                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Дата создания')

                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label('Дата обновления')

                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ip_address')
                    ->label('IP-адрес')
                ,
                IconEntry::make('approved')
                    ->label('Одобрен')

                    ->boolean(),
                TextEntry::make('post_id')
                    ->label('ID поста')

                    ->numeric(),
            ]);
    }
}
