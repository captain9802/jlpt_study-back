<?php
namespace App\Models;
use App\Models\FavoriteList;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'text',
        'reading',
        'meaning',
        'onyomi',
        'kunyomi',
        'examples',
        'breakdown',
    ];

    protected $casts = [
        'examples' => 'array',
        'breakdown' => 'array',
    ];

    public function wordList()
    {
        return $this->belongsTo(FavoriteList::class, 'list_id');
    }
}

