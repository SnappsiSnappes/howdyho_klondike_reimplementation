<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')

                    ->required(),
                TextInput::make('slug')
                    ->label('Slug для url')
                    ->required(),
                Select::make('parent_id')
                    ->label('Категория-родитель')
                    ->options(fn() => Category::buildTreeWithPaths())
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                FileUpload::make('collection_img')
                    ->label('Картинка коллекции 970 x 150')
                    ->disk('public')
                    ->directory('Category/collections') // ← отдельная папка (опционально)
                    ->image()
                    ->imagePreviewHeight('200')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp','image/webm', 'video/webm', 'image/gif', 'image/svg+xml'])
                    ->deleteUploadedFileUsing(function (?string $file) {
                        if ($file) {
                            Storage::disk('public')->delete($file);
                        }
                    }),
                FileUpload::make('preview_img')
                    ->label('Картинка категории')
                    ->disk('public')          // ← диск 'public' = storage/app/public
                    ->directory('Category')   // ← папка внутри диска
                    ->image()
                    ->imagePreviewHeight('200')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp','image/webm', 'video/webm', 'image/gif', 'image/svg+xml'])
                    ->deleteUploadedFileUsing(function ($file) {

                        if ($file) {
                            Storage::disk('public')->delete($file);
                        }
                    }),
            ]);
    }
}
