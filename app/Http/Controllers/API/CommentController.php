<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index($postId)
    {
        $comments = Comment::with(['user', 'admin', 'replies.admin', 'replies.user'])
            ->withCount('likedByUsers')
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->latest()
            ->get();
        return response()->json($comments);
    }

    public function store(Request $request, $postId)
    {
        $user = Auth::user(); // login via sanctum user

        $request->validate([
            'content' => 'required|string',
        ]);

        $comment = Comment::create([
            'content' => $request->content,
            'user_id' => $user->id,
            'post_id' => $postId,
        ]);

        return response()->json($comment, 201);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);

        $user = Auth::user();
        if ($comment->user_id !== $user->id) {
            return response()->json(['error' => 'Tidak bisa menghapus komentar orang lain'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Komentar berhasil dihapus']);
    }

    public function destroyByAdmin($id)
    {
        $comment = Comment::findOrFail($id);

        // Tidak perlu cek user, karena admin punya hak penuh
        $comment->delete();

        return response()->json(['message' => 'Komentar berhasil dihapus oleh admin']);
    }

    public function replyToComment(Request $request, $commentId)
    {
        $admin = auth('admin')->user(); // pakai guard admin

        $request->validate([
            'content' => 'required|string',
        ]);

        $parent = Comment::findOrFail($commentId);

        // Pastikan hanya 1 level reply
        if ($parent->parent_id !== null) {
            return response()->json(['error' => 'Hanya bisa membalas komentar utama'], 422);
        }

        $reply = \App\Models\Comment::create([
            'content' => $request->content,
            'admin_id' => $admin->id, // tidak pakai user_id, karena ini admin
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);

        return response()->json(['message' => 'Balasan berhasil ditambahkan', 'reply' => $reply]);
    }
    public function like($id)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($id);

        // Prevent duplicate like
        if ($comment->likedByUsers()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Kamu sudah menyukai komentar ini'], 409);
        }

        $comment->likedByUsers()->attach($user->id);

        return response()->json(['message' => 'Komentar disukai']);
    }

    public function unlike($id)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($id);

        $comment->likedByUsers()->detach($user->id);

        return response()->json(['message' => 'Like dihapus']);
    }
}
