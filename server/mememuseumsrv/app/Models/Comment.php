<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'body',
        'user_id',
        'meme_id',
        'parent_id',
        'is_anonymous',
        'author_name',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meme()
    {
        return $this->belongsTo(Meme::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Nome visuale (anonimo o utente)
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return $this->author_name ?? 'Anonymous';
        }
        return $this->user->name ?? 'Deleted User';
    }
}