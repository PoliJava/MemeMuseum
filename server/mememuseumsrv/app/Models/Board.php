<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'description', 'is_archived', 'is_readonly'];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_readonly' => 'boolean',
    ];

    public function memes()
    {
        return $this->hasMany(Meme::class);
    }
}