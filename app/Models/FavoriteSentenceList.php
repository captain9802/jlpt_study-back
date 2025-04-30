<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSentenceList extends Model
{
    use HasFactory;

    protected $table = 'favorite_sentence_lists';

    protected $fillable = ['user_id', 'title', 'color'];

    public function sentences()
    {
        return $this->hasMany(FavoriteSentence::class, 'list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
