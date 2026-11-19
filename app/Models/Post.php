<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        "title",
        "body",
        "user_id",
        "preview_text",
        "preview_image", 
        "category_id",
        "slug", 

    ];
    
    protected $table = 'post';


    // Автоматическая генерация slug при сохранении
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($post) {
            if (empty($post->slug) || $post->isDirty('title')) {
                $post->slug = static::uniqueSlug($post->title);
            }
        });
    }

    // Генерация уникального slug
    public static function uniqueSlug($title)
    {
        $slug = Str::slug($title, '-', 'ru'); // поддержка кириллицы
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    // Указываем Laravel использовать slug вместо id для маршрутов
    public function getRouteKeyName()
    {
        return 'slug';
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
