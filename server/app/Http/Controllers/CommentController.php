<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'meme_id'      => 'required|exists:memes,id',
            'body'         => 'nullable|string|max:2000',
            'image'        => 'nullable|image|max:2048',
            'parent_id'    => 'nullable|exists:comments,id',
            'is_anonymous' => 'boolean',
            'author_name'  => 'nullable|string|max:64',
        ]);

        // A reply must have at least a body or an image
        if (empty($data['body']) && !$request->hasFile('image')) {
            return response()->json([
                'message' => 'A reply must contain text or an image.',
            ], 422);
        }

        // parent must belong to the same thread
        if (!empty($data['parent_id'])) {
            $parent = Comment::find($data['parent_id']);
            if ($parent && $parent->meme_id !== (int) $data['meme_id']) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this thread.',
                ], 422);
            }
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('comments', 'public')
            : null;

        $comment = Comment::create([
            'meme_id'      => $data['meme_id'],
            'body'         => $data['body'] ?? null,
            'image_path'   => $imagePath,
            'parent_id'    => $data['parent_id'] ?? null,
            'user_id'      => $request->user()->id,
            'is_anonymous' => $data['is_anonymous'] ?? true,
            'author_name'  => $data['author_name'] ?? null,
        ]);

        $comment->load('user');

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment->update(['body' => $data['body']]);
        $comment->load('user');

        return (new CommentResource($comment))->response();
    }

    public function destroy(Comment $comment): JsonResponse
    {
        if ($comment->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}