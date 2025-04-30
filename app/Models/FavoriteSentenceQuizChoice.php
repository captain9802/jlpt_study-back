<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteSentenceQuizChoice extends Model
{
    use HasFactory;

    protected $table = 'favorite_sentence_quiz_choices';

    protected $fillable = ['quiz_id', 'text', 'meaning', 'is_correct'];

    public function quiz()
    {
        return $this->belongsTo(FavoriteSentenceQuiz::class, 'quiz_id');
    }
}
