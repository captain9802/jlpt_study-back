<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrammarChoice extends Model
{
    use HasFactory;

    protected $table = 'grammar_choices';

    protected $fillable = [
        'quiz_id',
        'text',
        'meaning',
        'is_correct',
        'explanation',
    ];

    public function quiz()
    {
        return $this->belongsTo(GrammarQuiz::class, 'quiz_id');
    }
}
