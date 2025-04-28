<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FavoriteGrammarList extends Model
{
    use HasFactory;

    protected $table = 'favorite_grammar_lists';

    protected $fillable = [
        'user_id',
        'title',
        'color',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grammars()
    {
        return $this->hasMany(FavoriteGrammar::class, 'list_id');
    }
}
