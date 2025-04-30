<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSentenceQuiz extends Model
{
    use HasFactory;

    protected $table = 'favorite_sentence_quizzes';

    protected $fillable = ['sentence_id', 'question', 'question_ko', 'explanation'];

    public function sentence()
    {
        return $this->belongsTo(FavoriteSentence::class, 'sentence_id');
    }

    public function choices()
    {
        return $this->hasMany(FavoriteSentenceQuizChoice::class, 'quiz_id');
    }
}
