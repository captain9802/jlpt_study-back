<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserAiSetting extends Model
{
    protected $fillable = [
        'user_id', 'name', 'personality', 'tone', 'voice', 'jlpt_level', 'language_mode'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

