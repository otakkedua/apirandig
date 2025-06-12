<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('author', 'tags', 'categories')->get();
        return response()->json($posts);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Admin $admin */
        $author = auth('admin')->user();

        if ($author->role !== 'author') {
            return response()->json(['error' => 'Hanya author yang boleh menulis'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $count = 1;

        while (Post::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
            'thumbnail' => $thumbnailPath,
            'is_published' => $validated['is_published'] ?? false,
            'author_id' => $author->id,
        ]);

        $post->tags()->sync($validated['tag_ids'] ?? []);
        $post->categories()->sync($validated['category_ids'] ?? []);

        return response()->json($post, 201);
    }

    public function show($id)
    {
        $post = Post::with('author', 'tags', 'categories')->findOrFail($id);
        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        /** @var \App\Models\Admin $author */
        $author = auth('admin')->user();

        if ($author->role !== 'author') {
            return response()->json(['error' => 'Hanya author yang boleh mengupdate'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_published' => 'boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $post = Post::where('author_id', $author->id)->findOrFail($id);

        // Generate slug jika title diubah
        if (isset($validated['title'])) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $count = 1;
            while (Post::where('slug', $slug)->where('id', '!=', $post->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $post->slug = $slug;
            $post->title = $validated['title'];
        }

        if (isset($validated['content'])) {
            $post->content = $validated['content'];
        }

        if ($request->hasFile('thumbnail')) {
            // Hapus thumbnail lama
            if ($post->thumbnail) {
                Storage::disk('public')->delete($post->thumbnail);
            }

            $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
            $post->thumbnail = $thumbnailPath;
        }

        if (isset($validated['is_published'])) {
            $post->is_published = $validated['is_published'];
        }

        $post->save();

        if (isset($validated['tag_ids'])) {
            $post->tags()->sync($validated['tag_ids']);
        }

        if (isset($validated['category_ids'])) {
            $post->categories()->sync($validated['category_ids']);
        }

        return response()->json(['message' => 'Post berhasil diperbarui', 'post' => $post]);
    }


    public function destroy($id)
    {
        /** @var \App\Models\Admin $author */
        $author = auth('admin')->user();

        if ($author->role !== 'author') {
            return response()->json(['error' => 'Hanya author yang boleh menghapus post'], 403);
        }

        $post = Post::where('author_id', $author->id)->findOrFail($id);

        // Hapus file thumbnail jika ada
        if ($post->thumbnail) {
            Storage::disk('public')->delete($post->thumbnail);
        }

        $post->tags()->detach();
        $post->categories()->detach();
        $post->delete();

        return response()->json(['message' => 'Post berhasil dihapus']);
    }
}
