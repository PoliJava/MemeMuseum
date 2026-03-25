<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Meme;
use App\Enums\MemeAge;
use Illuminate\Http\Request;

class MemeController extends Controller
{
    public function index()
    {
        return Inertia::render('Memes/Index', [
            'memes' => Meme::with(['user', 'tags'])->latest()->get(),
        ]);
    }

    public function show(Meme $meme)
    {
        return Inertia::render('Memes/Show', [
            'meme' => $meme->load(['user', 'tags', 'comments.user', 'votes']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'image_path' => 'required|string',
            'age'        => 'required|in:' . implode(',', array_column(MemeAge::cases(), 'value')),
        ]);

        $meme = Meme::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('memes.show', $meme);
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