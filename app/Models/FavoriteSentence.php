<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSentence extends Model
{
    use HasFactory;

    protected $table = 'favorite_sentences';

    protected $fillable = ['list_id', 'text', 'translation'];

    public function sentenceList()
    {
        return $this->belongsTo(FavoriteSentenceList::class, 'list_id');
    }

    public function words()
    {
        return $this->hasMany(FavoriteSentenceWord::class, 'sentence_id');
    }

    public function grammar()
    {
        return $this->hasMany(FavoriteSentenceGrammar::class, 'sentence_id');
    }

    public function quizzes()
    {
        return $this->hasMany(FavoriteSentenceQuiz::class, 'sentence_id');
    }
}
