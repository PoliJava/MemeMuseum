<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Meme;
use App\Enums\MemeAge;
use Illuminate\Http\Request;
use App\Http\Resources\MemeResource;


class MemeController extends Controller
{

public function index()
{
    $memes = Meme::with(['user', 'tags'])->latest()->paginate(10);
    return MemeResource::collection($memes);
}

public function show(Meme $meme)
{
    $meme->load(['user', 'tags', 'comments.user', 'votes']);
    return new MemeResource($meme);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'title'      => 'required|string|max:255',
        'image'      => 'required|image|max:2048', // gestione file
        'age'        => 'required|in:' . implode(',', array_column(MemeAge::cases(), 'value')),
        'tags'       => 'array|exists:tags,id',   // array di tag ids
    ]);

    // Salva l'immagine
    $path = $request->file('image')->store('memes', 'public');

    $meme = Meme::create([
        'title'      => $validated['title'],
        'image_path' => $path,
        'age'        => $validated['age'],
        'user_id'    => auth()->id(),
    ]);

    if (!empty($validated['tags'])) {
        $meme->tags()->sync($validated['tags']);
    }

    return new MemeResource($meme->load('tags', 'user'));
}
    public function update(Request $request, Meme $meme)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'age'   => 'sometimes|in:' . implode(',', array_column(MemeAge::cases(), 'value')),
        ]);

        $meme->update($validated);

        return redirect()->route('memes.show', $meme);
    }

    public function destroy(Meme $meme)
    {
        $meme->delete();

        return redirect()->route('memes.index');
    }
    

}