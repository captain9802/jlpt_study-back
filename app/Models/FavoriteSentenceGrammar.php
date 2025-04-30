<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSentenceGrammar extends Model
{
    use HasFactory;

    protected $table = 'favorite_sentence_grammar';

    protected $fillable = ['sentence_id', 'text', 'meaning'];

    public function sentence()
    {
        return $this->belongsTo(FavoriteSentence::class, 'sentence_id');
    }
}
