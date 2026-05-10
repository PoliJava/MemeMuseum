<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Meme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['meme_id' => 'required|exists:memes,id']);

        Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'meme_id' => $data['meme_id'],
        ]);

        return response()->json(['message' => 'Saved.'], 201);
    }

    public function destroy(Request $request, Meme $meme): JsonResponse
    {
        Favorite::where('user_id', $request->user()->id)
                ->where('meme_id', $meme->id)
                ->delete();

        return response()->json(['message' => 'Removed.']);
    }
}
