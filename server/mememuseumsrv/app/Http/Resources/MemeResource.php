<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'image_path'    => $this->image_path,
            'age'           => $this->age->value,
            'views_count'   => $this->views_count,
            'is_anonymous'  => $this->is_anonymous,
            'author_name'   => $this->when($this->is_anonymous, $this->author_name),
            'user'          => UserResource::make($this->whenLoaded('user')),
            'board'         => BoardResource::make($this->whenLoaded('board')),
            'tags'          => TagResource::collection($this->whenLoaded('tags')),
            'avg_rating'    => $this->avg_rating,
            'ratings_count' => $this->whenCounted('ratings'),
            'comments_count'=> $this->whenCounted('comments'),
            'created_at'    => $this->created_at,
        ];
    }
}