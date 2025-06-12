<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::with('posts')->get();
        return response()->json($tags);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:tags,name',
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'exists:posts,id',
        ]);

        $slug = Str::slug($validated['name']);

        $originalSlug = $slug;
        $count = 1;
        while (Tag::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        if (!empty($validated['post_ids'])) {
            $tag->posts()->sync($validated['post_ids']);
        }

        return response()->json($tag, 201);
    }

    public function show($id)
    {
        $tag = Tag::with('posts')->findOrFail($id);
        return response()->json($tag);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:tags,name,' . $tag->id,
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'exists:posts,id',
        ]);

        if (isset($validated['name'])) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Tag::where('slug', $slug)->where('id', '!=', $tag->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $tag->update([
                'name' => $validated['name'],
                'slug' => $slug,
            ]);
        }

        if (!empty($validated['post_ids'])) {
            $tag->posts()->sync($validated['post_ids']);
        }

        return response()->json($tag);
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->posts()->detach();
        $tag->delete();

        return response()->json(['message' => 'Tag berhasil dihapus']);
    }

    public function postsBySlug($slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $posts = $tag->posts()->with('tags', 'categories', 'author')->get();

        return response()->json([
            'tag' => $tag->name,
            'posts' => $posts
        ]);
    }
}
