<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class StoreProductController extends Controller
{
    public function index()
    {
        $products = Product::with('primaryImage')
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->orderBy('product_id')
            ->get()
            ->map(function ($product) {

                return [
                    'product_id' => $product->product_id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category,

                    'price' => (float) $product->unit_price,

                    'image_url' => $product->primaryImage
                        ? Storage::url(
                            $product->primaryImage->image_path
                        )
                        : null,
                ];
            });

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }
}