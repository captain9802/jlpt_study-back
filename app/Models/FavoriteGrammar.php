<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteGrammar extends Model
{
    use HasFactory;

    protected $table = 'favorite_grammars';

    protected $fillable = [
        'list_id',
        'grammar',
        'meaning',
    ];

    public function grammarList()
    {
        return $this->belongsTo(FavoriteGrammarList::class, 'list_id');
    }

    public function quizzes()
    {
        return $this->hasMany(GrammarQuiz::class, 'grammar_id');
    }

    public function examples()
    {
        return $this->hasMany(GrammarExample::class, 'grammar_id');
    }

}
