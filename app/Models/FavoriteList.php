<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteList extends Model
{
    use HasFactory;

    protected $table = 'favorite_word_lists';

    protected $fillable = ['user_id', 'title', 'color'];

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'list_id');
    }
}

