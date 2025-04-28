<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrammarQuiz extends Model
{
    use HasFactory;

    protected $table = 'grammar_quizzes';

    protected $fillable = [
        'grammar_id',
        'question',
        'translation',
        'answer',
    ];

    public function grammar()
    {
        return $this->belongsTo(FavoriteGrammar::class, 'grammar_id');
    }

    public function choices()
    {
        return $this->hasMany(GrammarChoice::class, 'quiz_id');
    }
}
