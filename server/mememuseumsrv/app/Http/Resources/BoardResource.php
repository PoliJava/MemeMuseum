<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'slug'        => $this->slug,
            'name'        => $this->name,
            'description' => $this->description,
            'is_archived' => $this->is_archived,
            'is_readonly' => $this->is_readonly,
            'memes_count' => $this->whenCounted('memes'),
            'created_at'  => $this->created_at,
        ];
    }
}