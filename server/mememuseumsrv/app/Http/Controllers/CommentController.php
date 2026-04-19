<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    /**
     * Store a newly created comment in storage.
     *
     * Expects:
     *   meme_id      int      required
     *   body         string   required
     *   parent_id    int|null optional – for threaded replies
     *   is_anonymous bool     optional (default true)
     *   author_name  string   optional – shown when is_anonymous
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'meme_id'      => 'required|exists:memes,id',
            'body'         => 'required|string|max:2000',
            'parent_id'    => 'nullable|exists:comments,id',
            'is_anonymous' => 'boolean',
            'author_name'  => 'nullable|string|max:64',
        ]);

        // Validate that parent_id (if given) belongs to the same meme
        if (!empty($data['parent_id'])) {
            $parent = Comment::find($data['parent_id']);
            if ($parent && $parent->meme_id !== (int) $data['meme_id']) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this thread.',
                ], 422);
            }
        }

        $comment = Comment::create([
            'meme_id'      => $data['meme_id'],
            'body'         => $data['body'],
            'parent_id'    => $data['parent_id'] ?? null,
            'user_id'      => $request->user()->id,
            'is_anonymous' => $data['is_anonymous'] ?? true,
            'author_name'  => $data['author_name'] ?? null,
        ]);

        // Load user so CommentResource can populate display_name correctly
        $comment->load('user');

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete a comment. Only the author may delete their own comment.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        if ($comment->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}