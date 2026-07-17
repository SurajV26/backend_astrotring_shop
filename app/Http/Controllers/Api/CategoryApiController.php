<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryApiController extends Controller
{
    /**
     * GET ALL CATEGORIES
     * Optional filters:
     *  - ?slug=puja-items
     *  - ?id=3
     */
    public function index(Request $request)
    {
        $query = Category::query();

        $query->where('status', 1);

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        if ($request->filled('slug')) {
            $query->where('slug', $request->slug);
        }

        $categories = $query
            ->select('id', 'name', 'slug', 'cat_image')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {

                $category->cat_image = $category->cat_image
                    ? asset('storage/' . $category->cat_image)
                    : null;

                return $category;
            });

        return response()->json([
            'status' => true,
            'count'  => $categories->count(),
            'data'   => $categories,
        ]);
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        $category->cat_image = $category->cat_image
            ? asset('storage/' . $category->cat_image)
            : null;

        return response()->json([
            'status' => true,
            'data'   => $category,
        ]);
    }
}
