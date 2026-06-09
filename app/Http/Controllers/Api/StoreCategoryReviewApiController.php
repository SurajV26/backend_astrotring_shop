<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreCategoryReview;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreCategoryReviewApiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required',
            'rating'   => 'required|integer|min:1|max:5',
            'review'   => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $category = Category::where('id', $request->category)
                ->orWhere('slug', $request->category)
                ->first();

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $review = StoreCategoryReview::updateOrCreate(
                [
                    'user_id'     => $request->user()->id,
                    'category_id' => $category->id,
                ],
                [
                    'rating' => $request->rating,
                    'review' => $request->review,
                ]
            );

            // Category::updateRating($category->id);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Review submitted successfully',
                'data'    => $review
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            \Log::error($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function categoryReviews($category)
    {
        $category = Category::where('id', $category)
            ->orWhere('slug', $category)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $reviews = StoreCategoryReview::with('user:id,name')
            ->where('category_id', $category->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $reviews
        ]);
    }
}