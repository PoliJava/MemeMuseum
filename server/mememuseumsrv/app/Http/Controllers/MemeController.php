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
    $memes = Meme::with(['user', 'tags'])->latest()->paginate(10); // paginazione richiesta
    return MemeResource::collection($memes);
}

public function show(Meme $meme)
{
    $meme->load(['user', 'tags', 'comments.user', 'votes']);
    return new MemeResource($meme);
}

public function store(StoreRequest $request) // <-- sostituisce Request
    {
        // $request->validated() contiene già i dati validati
        $path = $request->file('image')->store('memes', 'public');

        $meme = Meme::create([
            'title'      => $request->validated('title'),
            'image_path' => $path,
            'age'        => $request->validated('age'),
            'user_id'    => $request->user()->id,
        ]);

        if ($request->validated('tags')) {
            $meme->tags()->sync($request->validated('tags'));
        }

        return new MemeResource($meme->load('tags', 'user'));
    }

    public function update(UpdateRequest $request, Meme $meme) 
    {
        $meme->update($request->validated()); 
        return new MemeResource($meme);
    }

    public function destroy(Meme $meme)
    {
        $meme->delete();

        return redirect()->route('memes.index');
    }
    

}