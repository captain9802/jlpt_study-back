<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSentenceWord extends Model
{
    use HasFactory;

    protected $table = 'favorite_sentence_words';

    protected $fillable = ['sentence_id', 'text', 'reading', 'meaning'];

    public function sentence()
    {
        return $this->belongsTo(FavoriteSentence::class, 'sentence_id');
    }
}
