<?php

namespace App\Filament\Admin\Resources\ReactionElements;

use App\Filament\Admin\Resources\ReactionElements\Pages\CreateReactionElement;
use App\Filament\Admin\Resources\ReactionElements\Pages\EditReactionElement;
use App\Filament\Admin\Resources\ReactionElements\Pages\ListReactionElements;
use App\Filament\Admin\Resources\ReactionElements\Schemas\ReactionElementForm;
use App\Filament\Admin\Resources\ReactionElements\Tables\ReactionElementsTable;
use App\Models\ReactionElement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReactionElementResource extends Resource
{
    protected static ?string $model = ReactionElement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;
    protected static ?string $navigationLabel = 'Реакции'; // ← Это отображается в меню
    protected static ?string $modelLabel = 'Реакция';       // ← Для одной записи
    protected static ?string $pluralModelLabel = 'Реакции'; // ← Для нескольких записей

    public static function form(Schema $schema): Schema
    {
        return ReactionElementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReactionElementsTable::configure($table);
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
            'index' => ListReactionElements::route('/'),
            'create' => CreateReactionElement::route('/create'),
            'edit' => EditReactionElement::route('/{record}/edit'),
        ];
    }
}
