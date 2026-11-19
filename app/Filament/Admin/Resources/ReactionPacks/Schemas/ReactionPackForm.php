<?php

namespace App\Filament\Admin\Resources\ReactionPacks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;

class ReactionPackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
                TextInput::make('folder_name')
                    ->label('Название папки')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        // Получаем текущий (старый) путь к файлу
                        $oldPath = $get('preview_img');

                        // Если есть старый файл — удаляем его
                        if ($oldPath) {
                            Storage::disk('public')->delete($oldPath);
                        }

                        // Очищаем поле preview_img в форме
                        $set('preview_img', null);
                    }),

                FileUpload::make('preview_img')
                    ->label('Картинка пака')
                    ->disk('public')
                    ->visibility('public')
                    ->image()
                    ->imagePreviewHeight('200')
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/webp','image/webm', 'video/webm','image/gif','image/jpeg'])
                    ->required()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get): string {
                        $folderName = $get('folder_name');
                        if (!$folderName) {
                            throw new \Exception('Folder name is required.');
                        }

                        $directory = "Reactions/{$folderName}";
                        Storage::disk('public')->makeDirectory($directory);

                        $filename = $file->getClientOriginalName();
                        $file->storeAs($directory, $filename, 'public');

                        return "{$directory}/{$filename}";
                    })
                    ->deleteUploadedFileUsing(function ($file) {

                        if ($file) {
                            Storage::disk('public')->delete($file);
                        }
                    })
            ]);
    }
}
