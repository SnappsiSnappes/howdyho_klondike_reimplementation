<?php

// app/Models/PostReaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    protected $table = 'post_reaction'; 

    protected $fillable = [
        'post_id',
        'reaction_element_id', // ← заменено
        'ip_address',
    ];

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post():belongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function element():belongsTo
    {
        return $this->belongsTo(ReactionElement::class, 'reaction_element_id');
    }
}