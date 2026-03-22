<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'meme_id',
        'value', // 1 = upvote, -1 = downvote
    ];

    protected $casts = [
        'value' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meme()
    {
        return $this->belongsTo(Meme::class);
    }
}