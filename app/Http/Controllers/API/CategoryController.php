<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('posts')->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'exists:posts,id',
        ]);

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        if (!empty($validated['post_ids'])) {
            $category->posts()->sync($validated['post_ids']);
        }

        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = Category::with('posts')->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:categories,name,' . $category->id,
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'exists:posts,id',
        ]);

        if (isset($validated['name'])) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $category->update([
                'name' => $validated['name'],
                'slug' => $slug,
            ]);
        }

        if (!empty($validated['post_ids'])) {
            $category->posts()->sync($validated['post_ids']);
        }

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->posts()->detach();
        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }

    public function postsBySlug($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $posts = $category->posts()->with('tags', 'categories', 'author')->get();

        return response()->json([
            'category' => $category->name,
            'posts' => $posts
        ]);
    }
}
