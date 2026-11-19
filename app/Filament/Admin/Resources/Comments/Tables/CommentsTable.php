<?php

namespace App\Filament\Admin\Resources\Comments\Tables;

use App\Models\Comment;
use Filament\Actions\Action; // ✅ Теперь из Filament\Actions
use Filament\Actions\BulkAction; // ✅ Теперь из Filament\Actions
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup; // ✅ Теперь из Filament\Actions
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Имя пользователя')
                    ->searchable(),
                TextColumn::make('text')
                    ->label('Текст комментария')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('IP-адрес')
                    ->searchable(),
                IconColumn::make('approved')
                    ->label('Одобрен')
                    ->boolean(),
                TextColumn::make('post_id')
                    ->label('ID поста')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('Одобрены')
                    ->query(fn($query) => $query->where('approved', true)),
                Filter::make('Ожидают модерации')
                    ->label('Ожидают модерации')
                    ->query(fn($query) => $query->where('approved', false)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve') // ✅ Исправлено: имя должно быть валидным идентификатором
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn(Comment $record) => $record->update(['approved' => true]))
                    ->visible(fn(Comment $record) => !$record->approved), // Показывать, если НЕ одобрен

                Action::make('unapprove') // ✅ Другое имя
                    ->label('Отменить одобрение')
                    ->icon('heroicon-o-x-circle') // Иконка лучше подходит для "отмены"
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn(Comment $record) => $record->update(['approved' => false]))
                    ->visible(fn(Comment $record) => $record->approved), // Показывать, если одобрен
            ])
            ->toolbarActions([ // ✅ Одна секция toolbarActions
                BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(), // ✅ Удаление
                    BulkAction::make('approve_selected')
                        ->label('Одобрить выбранные')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['approved' => true])),
                    BulkAction::make('unapprove_selected')
                        ->label('Отменить одобрение')
                        ->icon('heroicon-o-x-circle') // Иконка "крестик" для отмены
                        ->color('warning') // Цвет warning для отмены
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['approved' => false])),
                ]),
            ]);
    }
}