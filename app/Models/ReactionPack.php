<?php

// app/Models/ReactionPack.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReactionPack extends Model
{
    protected $table = 'reaction_pack'; 

    protected $fillable = ['name', 'description', 'folder_name','preview_img'];

    public function elements():HasMany
    {
        return $this->hasMany(ReactionElement::class, 'reaction_pack_id');
    }
}