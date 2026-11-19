<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    protected $table = 'category';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'preview_img',
        'collection_img', 

    ];

    // Связь: одна категория → много постов
    public function post(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // Подкатегории (дети)
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Родительская категория
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public static function buildTreeWithPaths(): array
    {
        $categories = Category::with('parent:id,name,parent_id')->get();
        $map = [];

        foreach ($categories as $category) {
            $map[$category->id] = $category;
        }

        $options = [];
        foreach ($categories as $category) {
            $options[$category->id] = $category->getFullPath($map);
        }

        // Сортируем по full_path
        asort($options);

        return $options;
    }

    protected function getFullPath(array $map): string
    {
        $path = [$this->name];
        $current = $this;

        while ($current->parent_id && isset($map[$current->parent_id])) {
            $current = $map[$current->parent_id];
            array_unshift($path, $current->name);
        }

        return implode(' → ', $path);
    }
}
