<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // withAvg() result; falls back to the accessor for single-meme show()
        $avg = $this->ratings_avg_value !== null
            ? round((float) $this->ratings_avg_value * 2) / 2   // match the accessor's 0.5 rounding
            : $this->avg_rating;                                 // accessor — fine for single-meme show()

        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'body'           => $this->body,
            'image_path'     => $this->image_path,
            'age'            => $this->age->value,
            'views_count'    => $this->views_count,
            'is_anonymous'   => $this->is_anonymous,
            'author_name'    => $this->when($this->is_anonymous, $this->author_name),
            'user'           => UserResource::make($this->whenLoaded('user')),
            'board'          => BoardResource::make($this->whenLoaded('board')),
            'tags'           => TagResource::collection($this->whenLoaded('tags')),
            'comments'         => CommentResource::collection($this->whenLoaded('comments')),
            // Board-view preview: last 3 replies in chronological order
            'preview_comments' => $this->when(
                $this->relationLoaded('previewComments'),
                fn() => CommentResource::collection(
                    $this->previewComments->take(3)->reverse()->values()
                )
            ),
            'avg_rating'     => $avg,
            'my_rating'      => $this->when(
                $request->user() && $this->relationLoaded('ratings'),
                fn() => $this->ratings->where('user_id', $request->user()->id)->first()?->value
            ),
            'is_favorited'   => $this->when(
                $request->user() && $this->relationLoaded('favorites'),
                fn() => $this->favorites->contains('user_id', $request->user()->id)
            ),
            'ratings_count'  => $this->whenCounted('ratings'),
            'comments_count' => $this->whenCounted('comments'),
            'created_at'     => $this->created_at,
        ];
    }
}