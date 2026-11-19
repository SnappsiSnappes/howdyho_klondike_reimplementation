<?php

namespace App\Filament\Admin\Resources\ReactionTypes;

use App\Filament\Admin\Resources\ReactionTypes\Pages\CreateReactionType;
use App\Filament\Admin\Resources\ReactionTypes\Pages\EditReactionType;
use App\Filament\Admin\Resources\ReactionTypes\Pages\ListReactionTypes;
use App\Filament\Admin\Resources\ReactionTypes\Schemas\ReactionTypeForm;
use App\Filament\Admin\Resources\ReactionTypes\Tables\ReactionTypesTable;
use App\Models\ReactionType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReactionTypeResource extends Resource
{
    protected static ?string $model = ReactionType::class;
    protected static ?string $navigationLabel = 'Тип реакций'; // ← Это отображается в меню
    protected static ?string $modelLabel = 'Тип реакции';       // ← Для одной записи
    protected static ?string $pluralModelLabel = 'Тип реакций'; // ← Для нескольких записей

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function form(Schema $schema): Schema
    {
        return ReactionTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReactionTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReactionTypes::route('/'),
            'create' => CreateReactionType::route('/create'),
            'edit' => EditReactionType::route('/{record}/edit'),
        ];
    }
}
