<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'body'         => $this->body,
            'display_name' => $this->display_name,
            'is_anonymous' => $this->is_anonymous,
            'parent_id'    => $this->parent_id,
            'meme_id'      => $this->meme_id,
            'user'         => UserResource::make($this->whenLoaded('user')),
            'parent'       => CommentResource::make($this->whenLoaded('parent')),
            'replies'      => CommentResource::collection($this->whenLoaded('replies')),
            'created_at'   => $this->created_at,
        ];
    }
}