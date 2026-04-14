<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Meme;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'meme_id' => 'required|exists:memes,id',
            'value'   => 'required|numeric|min:0.5|max:5|multiple_of:0.5',
        ]);

        $rating = Rating::updateOrCreate(
            ['user_id' => $request->user()->id, 'meme_id' => $data['meme_id']],
            ['value'   => $data['value']]
        );

        $avg = Rating::where('meme_id', $data['meme_id'])->avg('value');
        $avgRounded = round($avg * 2) / 2;

        return response()->json([
            'rating'     => $rating,
            'avg_rating' => $avgRounded,
            'count'      => Rating::where('meme_id', $data['meme_id'])->count(),
        ]);
    }

    public function destroy(Request $request, Meme $meme)
    {
        Rating::where('user_id', $request->user()->id)
              ->where('meme_id', $meme->id)
              ->delete();

        return response()->json(['message' => 'Rating removed']);
    }
}