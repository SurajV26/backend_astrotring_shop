<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DB;

class ProductController extends AdminController
{
    public function getIndex(Request $request)
    {
        return view('admin.products.index');
    }

    public function getList(Request $request)
    {
        $list = Product::query()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'products.id',
                'products.name as product_name',
                'products.code as product_code',
                'products.stock_status',
                'products.status',
                'categories.name as category_name',
                'categories.code as category_code',
            ])
            ->when($request->filled('category_id'), function ($q) use ($request) {
    
                $q->where('products.category_id', $request->category_id);
    
            })
            ->when($request->filled('product_id'), function ($q) use ($request) {
    
                $q->where('products.id', $request->product_id);
    
            })
            ->when($request->filled('stock_status'), function ($q) use ($request) {
    
                $q->where('products.stock_status', $request->stock_status);
    
            })
            ->when($request->status !== null && $request->status !== "", function ($q) use ($request) {
    
                $q->where('products.status', $request->status);
    
            })
            ->orderBy('products.id', 'desc');
    
        return \DataTables::of($list)
            ->addColumn('category_name', function ($row) {
    
                return '[ <b>' . e($row->category_code) . '</b> ]<br>' .
                    e($row->category_name);
    
            })
            ->addColumn('code_name', function ($row) {
    
                return '[ <b>' . e($row->product_code) . '</b> ]<br>' .
                    e($row->product_name);
    
            })
            ->rawColumns(['category_name', 'code_name'])
            ->make();
    }

    public function getCreate()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function postCreate(Request $request)
    {

        $subName = $request->input('sub_name');
        
        if (is_string($subName)) {
            $subName = array_values(array_filter(
                array_map('trim', explode(',', $subName))
            ));
        }
        
        $request->merge([
            'sub_name' => $subName,
        ]);
        
        $request->validate([
            'category_id'  => 'required|exists:categories,id',
            'code'         => 'required|unique:products,code',
            'name'         => 'required|max:255',
            'sub_name'     => 'nullable|array',
            'slug'         => 'nullable|unique:products,slug',

            'stone_name'   => 'nullable|string|max:255',

            // 'ratti_options' => 'nullable|array',
            // 'ratti_options.*.ratti' => 'nullable|numeric|required_with:ratti_options.*.price',
            // 'ratti_options.*.price' => 'nullable|numeric|required_with:ratti_options.*.ratti',

            'ratti_options' => 'nullable|array',
            'ratti_options.*.ratti' => 'required|numeric',
            'ratti_options.*.ratti_afterPrice' => 'required|numeric',
            'ratti_options.*.ratti_beforePrice' => 'nullable|numeric',

            'description' => 'nullable|string',

            'benefits' => 'nullable|string',
            'how_to_use' => 'nullable|string',
            'purity' => 'nullable|string',

            'specifications' => 'nullable|array',
            'faq' => 'nullable|array',

            'shipping_info' => 'nullable|string',

            'origin' => 'nullable|string',
            'planet' => 'nullable|string',

            'lab_certificates' => 'nullable|array',
            'lab_certificates.*.image' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'lab_certificates.*.number' => 'nullable|string|max:255',

            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|array',

            'before_price' => 'nullable|numeric',
            'after_price'  => 'nullable|numeric',

            'hsn_code' => 'nullable|string|max:50',
            'gst_rate' => 'nullable|numeric|min:0|max:100',

            'weight'   => 'nullable|numeric|min:0',
            'length'   => 'nullable|numeric|min:0',
            'breadth'  => 'nullable|numeric|min:0',
            'height'   => 'nullable|numeric|min:0',

            'stock_qty'    => 'required|integer|min:0',

            'is_bestseller' => 'nullable|in:0,1',
            'status' => 'nullable|in:0,1',

            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,webm|max:20480',
        ]);

        DB::beginTransaction();

        $rattiOptions = $this->normalizeRatti($request->ratti_options);

        $labCertificates = [];

        if ($request->has('lab_certificates')) {

            foreach ($request->lab_certificates as $key => $cert) {

                $imageName = null;

                if ($request->hasFile("lab_certificates.$key.image")) {

                    $imageName = uploadFile(
                        "lab_certificates.$key.image", null, null, 'lab_certificates'
                    );
                }

                if ($imageName || !empty($cert['number'])) {

                    $labCertificates[] = [
                    'image' => $imageName,
                    'number' => $cert['number'] ?? null
                    ];

                }
            }
        }

        try {

            $product = Product::create([
                'category_id' => $request->category_id,
                'code' => $request->code,
                'name' => $request->name,
                'sub_name' => $request->sub_name,
                'slug' => Str::slug($request->slug ?: $request->name),

                'stone_name' => $request->stone_name,
                'ratti_options' => $rattiOptions,

                'description' => $request->description,

                'benefits' => $request->benefits,
                'how_to_use' => $request->how_to_use,
                'purity' => $request->purity,

                'specifications' => $this->normalizeSpecifications($request->specifications),
                'faq' => $this->normalizeFaq($request->faq),

                'shipping_info' => $request->shipping_info,

                'origin' => $request->origin,
                'planet' => $request->planet,

                'lab_certificates' => $labCertificates,

                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,

                'before_price' => $request->before_price,
                'after_price' => $request->after_price,

                'hsn_code' => $request->hsn_code ?? '71162000',
                'gst_rate' => $request->gst_rate ?? 3,

                'weight'  => $request->weight ?? 0,
                'length'  => $request->length ?? 0,
                'breadth' => $request->breadth ?? 0,
                'height'  => $request->height ?? 0,

                'stock_qty' => $request->stock_qty,
                'stock_status' => Product::resolveStockStatus($request->stock_qty),

                'is_bestseller' => (int)$request->input('is_bestseller', 0),
                'status' => (int)$request->input('status',1),
            ]);

            if ($request->hasFile('image')) {

                $product->image = uploadFile(
                    'image',
                    null,
                    null,
                    'product'
                );

                $product->save();
            }

            if ($request->hasFile('media')) {

                foreach ($request->file('media') as $index => $file) {

                    $fieldName = "media.$index";

                    $media = uploadMedia(
                        $fieldName,
                        null,
                        null,
                        'product'
                    );

                    if ($media) {

                        ProductImage::create([
                            'product_id' => $product->id,
                            'images' => basename($media['path']),
                            'type' => $media['type']
                        ]);

                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error($e);

            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getUpdate(Request $request)
    {
        $product = Product::with('images')->findOrFail($request->id);
        $categories = Category::orderBy('name')->get();

        return view('admin.products.update', compact('product', 'categories'));
    }

    public function postUpdate(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $subName = $request->input('sub_name');
        
        if (is_string($subName)) {
            $subName = array_values(array_filter(
                array_map('trim', explode(',', $subName))
            ));
        }
        
        $request->merge([
            'sub_name' => $subName,
        ]);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|max:100|unique:products,code,' . $product->id,
            'name' => 'required|max:255',
            'sub_name'     => 'nullable|array',
            'slug' => 'required|unique:products,slug,' . $product->id,

            'ratti_options' => 'nullable|array',
            'ratti_options.*.ratti' => 'required|numeric',
            'ratti_options.*.ratti_afterPrice' => 'required|numeric',
            'ratti_options.*.ratti_beforePrice' => 'nullable|numeric',

            'hsn_code' => 'nullable|string|max:50',
            'gst_rate' => 'nullable|numeric|min:0|max:100',

            'weight'   => 'nullable|numeric|min:0',
            'length'   => 'nullable|numeric|min:0',
            'breadth'  => 'nullable|numeric|min:0',
            'height'   => 'nullable|numeric|min:0',

            'lab_certificates' => 'nullable|array',
            'lab_certificates.*.image' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'lab_certificates.*.number' => 'nullable|string|max:255',

            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,webm|max:20480',
        ]);

        DB::beginTransaction();

        $labCertificates = [];

        $oldCertificates = $product->lab_certificates ?? [];

        if ($request->has('lab_certificates')) {

            foreach ($request->lab_certificates as $key => $cert) {

                $imageName = $oldCertificates[$key]['image'] ?? null;

                if ($request->hasFile("lab_certificates.$key.image")) {

                    if ($imageName && file_exists(public_path('storage/lab_certificates/'.$imageName))) {
                        unlink(public_path('storage/lab_certificates/'.$imageName));
                    }

                    $imageName = uploadFile(
                        "lab_certificates.$key.image",
                        null,
                        null,
                        'lab_certificates'
                    );
                }

                if ($imageName || !empty($cert['number'])) {

                    $labCertificates[] = [
                    'image' => $imageName,
                    'number' => $cert['number'] ?? null
                    ];

                }
            }
        }

        try {

            $product->update([

                'category_id' => $request->category_id,

                'name' => $request->name,
                
                'sub_name' => $request->sub_name,

                'slug' => Str::slug($request->slug),

                'stone_name' => $request->stone_name,

                'ratti_options' => $this->normalizeRatti($request->ratti_options),

                'description' => $request->description,

                'benefits' => $request->benefits,
                'how_to_use' => $request->how_to_use,
                'purity' => $request->purity,

                'specifications' => $this->normalizeSpecifications($request->specifications),

                'faq' => $this->normalizeFaq($request->faq),

                'shipping_info' => $request->shipping_info,

                'origin' => $request->origin,
                'planet' => $request->planet,

                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,

                'before_price' => $request->before_price,
                'after_price' => $request->after_price,

                'hsn_code' => $request->hsn_code ?? '71162000',
                'gst_rate' => $request->gst_rate ?? 3,

                'weight'  => $request->weight ?? 0,
                'length'  => $request->length ?? 0,
                'breadth' => $request->breadth ?? 0,
                'height'  => $request->height ?? 0,

                'stock_qty' => $request->stock_qty,
                'stock_status' => Product::resolveStockStatus($request->stock_qty),

                'is_bestseller' => (int)$request->input('is_bestseller', 0),
                'status' => (int)$request->input('status',1),

                'lab_certificates' => $labCertificates

                ]);

            if ($request->hasFile('image')) {

                if ($product->image && file_exists(public_path('storage/product/'.$product->image))) {
                    unlink(public_path('storage/product/'.$product->image));
                }

                $product->image = uploadFile(
                    'image',
                    null,
                    null,
                    'product'
                );

                $product->save();
            }

            if ($request->hasFile('media')) {

                foreach ($request->file('media') as $index => $file) {

                    $fieldName = "media.$index";

                    $media = uploadMedia(
                        $fieldName,
                        null,
                        null,
                        'product'
                    );

                    if ($media) {

                        ProductImage::create([
                            'product_id' => $product->id,
                            'images' => basename($media['path']),
                            'type' => $media['type']
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();
            \Log::error($e);

            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function removeImage($id)
    {
        $image = ProductImage::findOrFail($id);

        $path = public_path('storage/product/' . $image->images);

        if (file_exists($path)) {
            unlink($path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image removed successfully'
        ]);
    }

    public function getDelete(Request $request)
    {
        Product::findOrFail($request->id)->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function getChangeStatus(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->status = !$product->status;
        $product->save();

        return response()->json([
            'message' => 'Product status updated'
        ]);
    }

    private function normalizeSpecifications($specs)
    {
        return collect($specs ?? [])
            ->filter(fn($s) => !empty($s['title']) || !empty($s['value']))
            ->map(fn($s) => [
                'title' => trim($s['title'] ?? ''),
                'value' => trim($s['value'] ?? '')
            ])
            ->values()
            ->toArray();
    }

    private function normalizeFaq($faq)
    {
        return collect($faq ?? [])
            ->filter(fn($f) => !empty($f['question']) || !empty($f['answer']))
            ->map(fn($f) => [
                'question' => trim($f['question'] ?? ''),
                'answer' => trim($f['answer'] ?? '')
            ])
            ->values()
            ->toArray();
    }

    private function normalizeRatti($ratti)
    {
        return collect($ratti ?? [])
            ->filter(fn($r) =>
                !empty($r['ratti']) &&
                !empty($r['ratti_afterPrice'])
            )
            ->map(fn($r) => [
                'ratti' => (float) $r['ratti'],
                'ratti_afterPrice' => (float) $r['ratti_afterPrice'],
                'ratti_beforePrice' => isset($r['ratti_beforePrice'])
                    ? (float) $r['ratti_beforePrice']
                    : null,
            ])
            ->values()
            ->toArray();
    }
    
    public function getProductCode(Request $request)
    {
        $name = $request->name;
        $category = $request->category;
    
        if (!$name || !$category) {
            return response()->json(['code' => '']);
        }
    
        // Clean product name
        $productCode = strtoupper(substr(preg_replace('/[^a-z]/i', '', $name), 0, 2));
    
        // Clean category
        $categoryCode = strtoupper(substr(preg_replace('/[^a-z]/i', '', $category), 0, 2));
    
        $prefix = $categoryCode . $productCode;
    
        // Last code fetch karo
        $lastProduct = \App\Models\Product::where('code', 'LIKE', $prefix . '-%')
            ->orderBy('code', 'desc')
            ->first();
    
        if ($lastProduct) {
            $lastNumber = (int) substr($lastProduct->code, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
    
        $newCode = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    
        return response()->json(['code' => $newCode]);
    }

    public function getFilterData(Request $request)
    {
        $query = Product::query();

        if ($request->category_id) {    
            $query->where('category_id', $request->category_id);
        }

        if ($request->product_id) {
            $query->where('id', $request->product_id);
        }

        if ($request->stock_status) {
            $query->where('stock_status', $request->stock_status);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $products = (clone $query)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $stockStatuses = (clone $query)
            ->select('stock_status')
            ->distinct()
            ->pluck('stock_status');

        $statuses = (clone $query)
            ->select('status')
            ->distinct()
            ->pluck('status');

        return response()->json([
            'products' => $products,
            'stock_statuses' => $stockStatuses,
            'statuses' => $statuses,
        ]);
    }
}