<?php

use App\Http\Controllers\MemeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    fn(Request $r) => response()->json($r->user()));

    // Memes
    Route::post('/memes',           [MemeController::class, 'store']);
    Route::put('/memes/{meme}',     [MemeController::class, 'update']);
    Route::delete('/memes/{meme}',  [MemeController::class, 'destroy']);

    // Comments
    Route::post('/comments',              [CommentController::class, 'store']);
    Route::delete('/comments/{comment}',  [CommentController::class, 'destroy']);

    // Votes
    Route::post('/votes',         [VoteController::class, 'store']);
    Route::delete('/votes/{vote}',[VoteController::class, 'destroy']);

    // Tags
    Route::post('/memes/{meme}/tags',            [TagController::class, 'attach']);
    Route::delete('/memes/{meme}/tags/{tag}',    [TagController::class, 'detach']);
});

// Public meme browsing
Route::get('/memes',        [MemeController::class, 'index']);
Route::get('/memes/{meme}', [MemeController::class, 'show']);