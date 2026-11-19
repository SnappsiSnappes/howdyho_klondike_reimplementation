<?php

// app/Models/ReactionElement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReactionElement extends Model
{
    protected $table = 'reaction_element'; 

    protected $fillable = ['name', 'file_name', 'reaction_pack_id', 'type_id'];

    // 👇 Обязательно должны быть эти методы
    public function pack(): BelongsTo
    {
        return $this->belongsTo(ReactionPack::class, 'reaction_pack_id');
    }


    public function type(): BelongsTo
    {
        return $this->belongsTo(ReactionType::class, 'type_id');
    }
}