<?php

// app/Models/ReactionType.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReactionType extends Model
{
    protected $table = 'reaction_type'; 

    protected $fillable = ['name','sticker'];

    public function elements():HasMany
    {
        return $this->hasMany(ReactionElement::class, 'type_id');
    }
}