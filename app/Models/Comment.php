<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'text',
        'ip_address',
        'approved',
        'post_id', // Добавляем связь с постом
    ];

    protected $casts = [
        'approved' => 'boolean',
    ];

    // Связь: комментарий принадлежит одному посту
public function post()
{
    return $this->belongsTo(Post::class, 'post_id', 'id');
}
}