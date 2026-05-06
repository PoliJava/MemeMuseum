<?php

namespace App\Http\Controllers;

use App\Models\Meme;
use App\Models\Tag;
use App\Enums\MemeAge;
use Illuminate\Http\Request;
use App\Http\Resources\MemeResource;
use App\Http\Requests\Meme\StoreRequest;   
use App\Http\Requests\Meme\UpdateRequest;

class MemeController extends Controller
{
    public function index(Request $request)
    {
        $query = Meme::with(['user', 'tags', 'board'])
                     ->withAvg('ratings', 'value')
                     ->withCount('comments');

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('name', $request->tag));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sort = $request->get('sort', 'newest');
        if ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'top_rated') {
            $query->orderByDesc('ratings_avg_value');
        } else {
            $query->latest();
        }

        return MemeResource::collection($query->paginate(10));
    }

    public function today()
    {
        $ids = Meme::orderBy('id')->pluck('id');

        if ($ids->isEmpty()) {
            return response()->json(['data' => null]);
        }

        $index = (int) date('Ymd') % $ids->count();
        $meme  = Meme::with(['user', 'tags', 'board'])
                     ->withAvg('ratings', 'value')
                     ->find($ids->get($index));

        return new MemeResource($meme);
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
            'body'         => $request->validated('body'),
            'image_path'   => $path,
            'age'          => $request->validated('age'),
            'user_id'      => $request->user()->id,
            'board_id'     => $request->validated('board_id'),
            'is_anonymous' => $request->validated('is_anonymous') ?? true,
            'author_name'  => $request->validated('author_name'),
        ]);

        if ($request->validated('tags')) {
            $tagIds = collect($request->validated('tags'))
                ->map(fn($name) => Tag::firstOrCreate(['name' => trim($name)])->id)
                ->toArray();
            $meme->tags()->sync($tagIds);
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