<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GrammarExample extends Model
{
    use HasFactory;

    protected $fillable = ['grammar_id', 'ja', 'ko'];

    public function grammar()
    {
        return $this->belongsTo(FavoriteGrammar::class, 'grammar_id');
    }
}
