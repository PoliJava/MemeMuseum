<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Http\Resources\BoardResource;
use App\Http\Resources\MemeResource;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index()
    {
        $boards = Board::withCount('memes')->get();
        return BoardResource::collection($boards);
    }

    public function show(Board $board)
    {
        $memes = $board->memes()
            ->with(['user', 'tags'])
            ->withCount('comments')    
            ->withAvg('ratings', 'value')
            ->latest()
            ->paginate(15);

        return MemeResource::collection($memes)
            ->additional(['board' => new BoardResource($board)]);
    }
}