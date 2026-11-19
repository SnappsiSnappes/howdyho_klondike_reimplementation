<?php

namespace App\Filament\Admin\Resources\ReactionPacks;

use App\Filament\Admin\Resources\ReactionPacks\Pages\CreateReactionPack;
use App\Filament\Admin\Resources\ReactionPacks\Pages\EditReactionPack;
use App\Filament\Admin\Resources\ReactionPacks\Pages\ListReactionPacks;
use App\Filament\Admin\Resources\ReactionPacks\Schemas\ReactionPackForm;
use App\Filament\Admin\Resources\ReactionPacks\Tables\ReactionPacksTable;
use App\Models\ReactionPack;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReactionPackResource extends Resource
{
    protected static ?string $model = ReactionPack::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Паки реакций'; // ← Это отображается в меню
    protected static ?string $modelLabel = 'Пак реакций';       // ← Для одной записи
    protected static ?string $pluralModelLabel = 'Паки реакций'; // ← Для нескольких записей

    public static function form(Schema $schema): Schema
    {
        return ReactionPackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReactionPacksTable::configure($table);
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
            'index' => ListReactionPacks::route('/'),
            'create' => CreateReactionPack::route('/create'),
            'edit' => EditReactionPack::route('/{record}/edit'),
        ];
    }
}
