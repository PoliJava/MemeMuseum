<?php

namespace App\Http\Controllers;

use App\Models\Meme;
use App\Enums\MemeAge;
use Illuminate\Http\Request;
use App\Http\Resources\MemeResource;
use App\Http\Requests\Meme\StoreRequest;   
use App\Http\Requests\Meme\UpdateRequest;

class MemeController extends Controller
{
    public function index()
    {
        $memes = Meme::with(['user', 'tags', 'board'])
                    ->withAvg('ratings', 'value')
                    ->latest()
                    ->paginate(10);
        return MemeResource::collection($memes);
    }

    public function show(Meme $meme)
    {
        $meme->load(['user', 'tags', 'board', 'comments.user', 'ratings']);
        $meme->incrementViews(); // Aumenta il contatore visualizzazioni
        return new MemeResource($meme);
    }

    public function store(StoreRequest $request)
    {
        $path = $request->file('image')->store('memes', 'public');

        $meme = Meme::create([
            'title'        => $request->validated('title'),
            'image_path'   => $path,
            'age'          => $request->validated('age'),
            'user_id'      => $request->user()->id,
            'board_id'     => $request->validated('board_id'),
            'is_anonymous' => $request->validated('is_anonymous') ?? true,
            'author_name'  => $request->validated('author_name'),
        ]);

        if ($request->validated('tags')) {
            $meme->tags()->sync($request->validated('tags'));
        }

        return new MemeResource($meme->load(['tags', 'user', 'board']));
    }

    public function update(UpdateRequest $request, Meme $meme)
    {
        $meme->update($request->validated());
        return new MemeResource($meme->load(['tags', 'user', 'board']));
    }

    public function destroy(Meme $meme)
    {
        $meme->delete();
        return response()->json(['message' => 'Meme deleted']);
    }
}