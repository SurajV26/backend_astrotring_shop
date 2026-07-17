<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends AdminController
{
    public function getIndex(Request $request)
    {
        return view('admin.product_categories.index');
    }

    public function getList(Request $request)
    {
        $list = Category::query()
            ->when($request->id, function ($q) use ($request) {
                $q->where('id', $request->id);
            })
            ->when($request->status !== null && $request->status !== "", fn($q) => $q->where("status", $request->status))
            ->orderBy('id', 'desc');

        return \DataTables::of($list)

            ->addColumn('image', function ($row) {

                if ($row->cat_image) {
                    return '<img src="'.asset('storage/categories/'.$row->cat_image).'"
                                width="50"
                                height="50"
                                class="rounded border"
                                style="object-fit:cover;">';
                }

                return '<span class="badge bg-secondary">No Image</span>';
            })

            ->addColumn('code_name', function ($row) {
                return '[ <b>'.e($row->code).'</b> ]<br>'.e($row->name);
            })

            ->rawColumns(['image', 'code_name'])
            ->make();
    }

    public function getCreate()
    {
        return view('admin.product_categories.create');
    }

    public function postCreate(Request $request)
    {
        $request->validate([
            'code'      => 'required|unique:categories,code',
            'name'      => 'required|unique:categories,name',
            'slug'      => 'nullable|unique:categories,slug',
            'cat_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'    => 'nullable|in:0,1',
        ]);

        try {

            $image = null;

            if ($request->hasFile('cat_image')) {
                $image = uploadFile('cat_image', null, null, 'categories');
            }

            Category::create([
                'code'      => $request->code,
                'name'      => $request->name,
                'slug'      => Str::slug($request->slug ?: $request->name),
                'cat_image' => $image,
                'status'    => (int) $request->input('status', 0),
            ]);

            return response()->json([
                'message' => 'Category created successfully'
            ]);

        } catch (\Throwable $e) {

            \Log::error($e);

            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getUpdate(Request $request)
    {
        $category = Category::findOrFail($request->id);

        return view('admin.product_categories.update', compact('category'));
    }

    public function postUpdate(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'code'      => 'required|unique:categories,code,' . $category->id,
            'name'      => 'required|unique:categories,name,' . $category->id,
            'slug'      => 'required|unique:categories,slug,' . $category->id,
            'cat_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'    => 'nullable|in:1,0',
        ]);

        $image = $category->cat_image;

        if ($request->hasFile('cat_image')) {
            $image = uploadFile(
                'cat_image',
                null,
                null,
                'categories',
                $category->cat_image
            );
        }

        $category->update([
            'code'      => $request->code,
            'name'      => $request->name,
            'slug'      => Str::slug($request->slug),
            'cat_image' => $image,
            'status'    => $request->status ?? 1,
        ]);

        return response()->json([
            'message' => 'Category updated successfully'
        ]);
    }

    public function getDelete(Request $request)
    {
        $category = Category::findOrFail($request->id);

        if ($category->cat_image && Storage::disk('public')->exists($category->cat_image)) {
            Storage::disk('public')->delete($category->cat_image);
        }

        $category->delete();

        return response()->json([
            'message' => 'Your request processed successfully.'
        ]);
    }

    public function getChangeStatus(Request $request)
    {
        $category = \App\Models\Category::findOrFail($request->id);
        if (! blank($category)) {
            $category->status = (int) ! $category->status;
            $category->save();
        }

        return response()->json(['message' => 'Your request processed successfully.']);
    }

    public function getCategoryCode(Request $request)
    {
        $name = $request->name;

        if (!$name) {
            return response()->json(['code' => '']);
        }

        $categoryCode = strtoupper(substr(preg_replace('/[^a-z]/i', '', $name), 0, 3));

        $prefix = $categoryCode;

        $lastCategory = \App\Models\Category::where('code', 'LIKE', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(code, '-', -1) AS UNSIGNED) DESC")
            ->first();

        if ($lastCategory) {
            $lastNumber = (int) substr($lastCategory->code, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $newCode = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return response()->json(['code' => $newCode]);
    }
}