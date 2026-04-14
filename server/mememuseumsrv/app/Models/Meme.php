<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\MemeAge;

class Meme extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_path',
        'user_id',
        'board_id',
        'age',
        'is_anonymous',
        'author_name',
        'views_count',
    ];

    protected $casts = [
        'age' => MemeAge::class,
        'is_anonymous' => 'boolean',
        'views_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'meme_tag', 'meme_id', 'tag_id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    // Media voti (arrotondata a 0.5)
    public function getAvgRatingAttribute(): ?float
    {
        $avg = $this->ratings()->avg('value');
        return $avg ? round($avg * 2) / 2 : null;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}