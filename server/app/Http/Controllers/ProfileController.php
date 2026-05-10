<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $memes = $user->memes()
            ->with('board')
            ->withAvg('ratings', 'value')
            ->latest()
            ->get();

        $rated     = $memes->filter(fn($m) => !is_null($m->ratings_avg_value));
        $avgRating = $rated->isNotEmpty()
            ? round($rated->avg('ratings_avg_value') * 2) / 2
            : null;

        $byBoard = $memes
            ->groupBy('board_id')
            ->map(fn($group) => [
                'board_slug' => $group->first()->board->slug,
                'board_name' => $group->first()->board->name,
                'count'      => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $recent = $memes->take(6)->map(fn($m) => [
            'id'         => $m->id,
            'title'      => $m->title,
            'image_path' => $m->image_path,
            'board_slug' => $m->board->slug,
            'avg_rating' => $m->ratings_avg_value
                ? round($m->ratings_avg_value * 2) / 2
                : null,
            'created_at' => $m->created_at,
        ])->values();

        $favorites = $user->favorites()
            ->with(['meme.board'])
            ->latest()
            ->get()
            ->map(fn($f) => [
                'id'         => $f->meme->id,
                'title'      => $f->meme->title,
                'image_path' => $f->meme->image_path,
                'board_slug' => $f->meme->board->slug,
            ])->values();

        return response()->json([
            'name'        => $user->name,
            'email'       => $user->email,
            'avatar_path' => $user->avatar_path,
            'created_at'  => $user->created_at,
            'memes_count' => $memes->count(),
            'avg_rating'  => $avgRating,
            'by_board'    => $byBoard,
            'recent'      => $recent,
            'favorites'   => $favorites,
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        return response()->json(['avatar_path' => $path]);
    }
}
