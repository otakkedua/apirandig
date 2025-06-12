<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostLikeController extends Controller
{
    public function like($postId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        Post::findOrFail($postId);

        if ($user->likedPosts()->where('post_id', $postId)->exists()) {
            return response()->json(['message' => 'Sudah like'], 409);
        }

        $user->likedPosts()->attach($postId);
        return response()->json(['message' => 'Berhasil like']);
    }

    public function unlike($postId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->likedPosts()->detach($postId);

        return response()->json(['message' => 'Berhasil unlike']);
    }

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $likes = $user->likedPosts()->with('author')->latest()->get();

        return response()->json($likes);
    }
}
