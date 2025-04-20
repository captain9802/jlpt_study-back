<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JlptWord extends Model
{
    protected $fillable = ['word', 'kana', 'meaning_ko', 'levels'];

    protected $casts = [
        'levels' => 'array',
    ];
}
