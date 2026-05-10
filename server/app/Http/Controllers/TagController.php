<?php

namespace App\Http\Controllers;

use App\Models\Meme;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
class TagController extends Controller
{
    public function attach(Request $request, Meme $meme)
    {
        if ($meme->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'tags'   => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $meme->tags()->syncWithoutDetaching($data['tags']);
        return response()->json(['message' => 'Tags attached']);
    }

    public function detach(Request $request, Meme $meme, Tag $tag)
    {
        if ($meme->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $meme->tags()->detach($tag->id);
        return response()->json(['message' => 'Tag detached']);
    }

    public function popular()
{
    $tags = Tag::withCount('memes')
               ->orderBy('memes_count', 'desc')
               ->limit(20)
               ->get();
    return TagResource::collection($tags);
}
}