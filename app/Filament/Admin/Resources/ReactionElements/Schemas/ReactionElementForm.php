<?php

namespace App\Filament\Admin\Resources\ReactionElements\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;

class ReactionElementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),

                FileUpload::make('file_name')
                    ->label('Имя файла')
                    ->disk('public')
                    ->visibility('public')
                    ->acceptedFileTypes(['application/json', 'image/webm', 'video/webm', 'image/png', 'image/gif', 'image/jpeg', 'image/webp','image/svg+xml'])
                    ->required()
                    ->rule(function (callable $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            // Пропускаем, если это не загруженный файл (например, при редактировании — может быть строка)
                            if (!$value instanceof TemporaryUploadedFile) {
                                return;
                            }

                            $packId = $get('reaction_pack_id');
                            if (!$packId) {
                                // Если пак не выбран — ошибка будет от другого правила (required)
                                return;
                            }

                            $pack = \App\Models\ReactionPack::find($packId);
                            if (!$pack || !$pack->folder_name) {
                                return; // или можно $fail('Invalid reaction pack.')
                            }

                            $expectedPath = "Reactions/{$pack->folder_name}/{$value->getClientOriginalName()}";

                            if (\App\Models\ReactionElement::where('file_name', $expectedPath)->exists()) {
                                $fail('A file with this name already exists in the selected pack.');
                            }
                        };
                    })
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get): string {
                        $packId = $get('reaction_pack_id');
                        if (!$packId) {
                            throw new \Exception('Reaction pack is required.');
                        }

                        $pack = \App\Models\ReactionPack::findOrFail($packId);
                        $directory = "Reactions/{$pack->folder_name}";
                        Storage::disk('public')->makeDirectory($directory);

                        $filename = $file->getClientOriginalName();
                        $file->storeAs($directory, $filename, 'public');

                        return "{$directory}/{$filename}";
                    })
                    ->deleteUploadedFileUsing(function ($file) {
                        if ($file && is_string($file)) {
                            Storage::disk('public')->delete($file);
                        }
                    }),

                Select::make('reaction_pack_id')
                    ->label('ID пакета реакций')
                    ->relationship('pack', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $get, callable $set) {
                        // Удаляем старый файл при смене пака
                        $oldPath = $get('file_name');
                        if ($oldPath) {
                            Storage::disk('public')->delete($oldPath);
                        }
                        $set('file_name', null);
                    }),

                Select::make('type_id')
                    ->label('Тип')
                    ->relationship('type', 'name')
                    ->required(),
            ]);
    }
}
