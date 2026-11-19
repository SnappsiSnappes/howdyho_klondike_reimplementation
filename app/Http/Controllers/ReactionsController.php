<?php

namespace App\Http\Controllers;

use App\Models\ReactionElement;
use App\Models\ReactionType;
use Illuminate\Http\Request;
use App\Models\ReactionPack;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ReactionsController extends Controller
{
    public function GetAllReactions()
    {
        // Все паки (только id и name)
        $allPacks = ReactionPack::all(['id', 'name', 'preview_img']);
        // Элементы первого пака (если есть)
        $firstPackElements = [];
        if ($firstPack = $allPacks->first()) {
            $firstPackElements = ReactionElement::with('type:id,name,sticker')
                ->where('reaction_pack_id', $firstPack->id)
                ->select('id', 'name', 'file_name', 'reaction_pack_id', 'type_id')
                ->get();
        }
        return response()->json([
            'all_packs' => $allPacks,
            'first_pack_elements' => $firstPackElements
        ]);
    }
    public function GetCertainPack($PackId)
    {
        $Elements = ReactionElement::with('type:id,name,sticker')
            ->where('reaction_pack_id', $PackId)
            ->select('id', 'name', 'file_name', 'reaction_pack_id', 'type_id')
            ->get();
        return response()->json(['Elements' => $Elements]);
    }
    public function SearchReaction(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:100'
        ]);
        $query = $request->input('query');
        if (!$query || strlen($query) < 2) {
            return response()->json(['Elements' => []]);
        }
        $elements = ReactionElement::with('type:id,name,sticker')
            ->where('name', 'like', '%' . $query . '%')
            ->select('id', 'name', 'file_name', 'reaction_pack_id', 'type_id')
            ->get();
        return response()->json(['Elements' => $elements]);
    }


    public function getReactionPacks()
    {
        return ReactionPack::select('id', 'name')->get();
    }

    public function getReactionTypes()
    {
        return ReactionType::select('id', 'name', 'sticker')->get();
    }


public function uploadPack(Request $request)
{
    $request->validate([
        'pack_id' => 'required|exists:reaction_pack,id',
        'type_id' => 'required|exists:reaction_type,id',
        'zip_file' => 'required|file|mimes:zip|max:1000240',
    ]);

    $pack = ReactionPack::findOrFail($request->pack_id);
    $type = ReactionType::findOrFail($request->type_id);

    if (empty($pack->folder_name)) {
        return response()->json(['error' => 'У пака не указан folder_name'], 400);
    }

    // Создаём временную директорию
    $tempDir = storage_path('app/temp_uploads/' . Str::random(20));
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Распаковываем ZIP
    $zip = new ZipArchive;
    if ($zip->open($request->file('zip_file')->getPathname()) !== true) {
        return response()->json(['error' => 'Не удалось открыть ZIP-файл'], 400);
    }
    $zip->extractTo($tempDir);
    $zip->close();

    // Рекурсивно получаем все файлы
    $files = $this->getAllFilesRecursively($tempDir);
    $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp','webm'];
    $elements = [];

    $packDir = "Reactions/{$pack->folder_name}";
    Storage::disk('public')->makeDirectory($packDir);

    foreach ($files as $filePath) {
        $filename = basename($filePath);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            continue;
        }

        $originalName = pathinfo($filename, PATHINFO_FILENAME);
        $safeName = Str::slug($originalName, '-', 'ru');
        if (empty($safeName)) {
            $safeName = Str::slug(uniqid(), '-', 'ru');
        }

        $newFilename = $safeName . '.' . $ext;
        $destination = "{$packDir}/{$newFilename}";

        // Уникальность
        $counter = 1;
        while (Storage::disk('public')->exists($destination)) {
            $newFilename = "{$safeName}-{$counter}.{$ext}";
            $destination = "{$packDir}/{$newFilename}";
            $counter++;
        }

        // Копируем в public
        Storage::disk('public')->put(
            $destination,
            file_get_contents($filePath)
        );

        $elementName = trim(Str::replace(['_', '-'], ' ', $safeName));

        $element = ReactionElement::create([
            'name' => $elementName,
            'file_name' => $destination,
            'reaction_pack_id' => $pack->id,
            'type_id' => $type->id,
        ]);

        $elements[] = $element;
    }

    // Удаляем временные файлы
    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);

    if (empty($elements)) {
        // 🔴 Отладка: посмотри, что было в архиве
        $allExtracted = $this->getAllFilesRecursively($tempDir, true); // включая недопустимые
        \Log::info('Extracted files from ZIP:', $allExtracted);
        return response()->json([
            'error' => 'В архиве не найдено подходящих изображений (png, jpg, jpeg, webp,webm). Найденные файлы: ' . implode(', ', array_map('basename', $allExtracted))
        ], 400);
    }

    if (empty($pack->preview_img)) {
        $pack->update(['preview_img' => $elements[0]->file_name]);
    }

    return response()->json([
        'message' => 'Реакции успешно добавлены!',
        'elements_count' => count($elements),
    ]);
}

// Вспомогательный метод: рекурсивный обход папки
private function getAllFilesRecursively(string $dir, bool $includeInvalid = false): array
{
    $files = [];
    if (!is_dir($dir)) return $files;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            if ($includeInvalid || in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'webp','webm'])) {
                $files[] = $file->getRealPath();
            }
        }
    }

    return $files;
}
}
