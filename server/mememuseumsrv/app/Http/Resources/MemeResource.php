<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // avg_rating: prefer the pre-aggregated value injected by withAvg('ratings', 'value')
        // which Laravel puts in $this->ratings_avg_value. Falls back to the model accessor
        // (which fires an extra query) only when the meme is loaded individually via show().
        $avg = $this->ratings_avg_value !== null
            ? round((float) $this->ratings_avg_value * 2) / 2   // match the accessor's 0.5 rounding
            : $this->avg_rating;                                 // accessor — fine for single-meme show()

        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'image_path'     => $this->image_path,
            'age'            => $this->age->value,
            'views_count'    => $this->views_count,
            'is_anonymous'   => $this->is_anonymous,
            'author_name'    => $this->when($this->is_anonymous, $this->author_name),
            'user'           => UserResource::make($this->whenLoaded('user')),
            'board'          => BoardResource::make($this->whenLoaded('board')),
            'tags'           => TagResource::collection($this->whenLoaded('tags')),
            'comments'       => CommentResource::collection($this->whenLoaded('comments')),
            'avg_rating'     => $avg,
            'ratings_count'  => $this->whenCounted('ratings'),
            'comments_count' => $this->whenCounted('comments'),
            'created_at'     => $this->created_at,
        ];
    }
}