<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    // Bookmark a post
    public function store($postId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->bookmarkedPosts()->where('post_id', $postId)->exists()) {
            return response()->json(['message' => 'Sudah dibookmark'], 409);
        }

        $user->bookmarkedPosts()->attach($postId);
        return response()->json(['message' => 'Berhasil bookmark']);
    }

    // Unbookmark a post
    public function destroy($postId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->bookmarkedPosts()->detach($postId);
        return response()->json(['message' => 'Bookmark dihapus']);
    }

    // List all bookmarked posts
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $bookmarks = $user->bookmarkedPosts()->with('author')->latest()->get();

        return response()->json($bookmarks);
    }
}
