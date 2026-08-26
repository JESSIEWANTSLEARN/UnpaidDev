<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSuperAdminSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuperAdminCatalogController extends Controller
{
    use HandlesSuperAdminSupport;

    public function storeProduct(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('WBO_Products', 'sku')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('WBO_Suppliers', 'supplier_id')],
            'abc_class' => ['required', Rule::in(['A', 'B', 'C'])],
            'is_seasonal' => ['required', 'boolean'],
            'is_visible' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        try {
            $productId = DB::transaction(function () use ($validated, $imagePath) {
                $productId = DB::table('WBO_Products')->insertGetId([
                    'sku' => $validated['sku'],
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?: null,
                    'category' => $validated['category'] ?: null,
                    'supplier_id' => $validated['supplier_id'] ?: null,
                    'abc_class' => $validated['abc_class'],
                    'is_seasonal' => (bool) $validated['is_seasonal'],
                    'is_visible' => (bool) $validated['is_visible'],
                    'is_featured' => (bool) $validated['is_featured'],
                    'unit_cost' => $validated['unit_cost'],
                    'unit_price' => $validated['unit_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($imagePath) {
                    DB::table('WBO_ProductImages')->insert([
                        'product_id' => $productId,
                        'image_path' => $imagePath,
                        'alt_text' => $validated['name'],
                        'is_primary' => true,
                        'sort_order' => 0,
                        'uploaded_by' => session('user_id'),
                        'created_at' => now(),
                    ]);
                }

                return $productId;
            });
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        $this->audit(
            $request,
            'PRODUCT_ADDED',
            "Added product {$validated['sku']} ({$validated['name']})."
        );

        return response()->json([
            'message' => 'Product added successfully.',
            'product_id' => $productId,
        ], 201);
    }

    public function stockIn(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('WBO_Products', 'product_id')],
            'batch_number' => ['required', 'string', 'max:50'],
            'quantity_received' => ['required', 'integer', 'min:1'],
            'expiry_date' => ['nullable', 'date'],
        ]);

        $duplicate = DB::table('WBO_Batches')
            ->where('product_id', $validated['product_id'])
            ->where('batch_number', $validated['batch_number'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'batch_number' => ['That batch number already exists for the selected product.'],
            ]);
        }

        $batchId = DB::transaction(function () use ($validated) {
            $batchId = DB::table('WBO_Batches')->insertGetId([
                'product_id' => $validated['product_id'],
                'batch_number' => $validated['batch_number'],
                'quantity_received' => $validated['quantity_received'],
                'current_quantity' => $validated['quantity_received'],
                'received_date' => now(),
                'expiry_date' => $validated['expiry_date'] ?: null,
            ]);

            DB::table('WBO_Transactions')->insert([
                'batch_id' => $batchId,
                'transaction_type' => 'RECEIVE',
                'quantity_change' => $validated['quantity_received'],
                'timestamp' => now(),
                'order_id' => null,
                'performed_by_user_id' => session('user_id'),
            ]);

            return $batchId;
        });

        $this->audit(
            $request,
            'STOCK_RECEIVED',
            "Stocked in batch {$validated['batch_number']} with {$validated['quantity_received']} unit(s)."
        );

        return response()->json([
            'message' => 'Stock received successfully.',
            'batch_id' => $batchId,
        ], 201);
    }


    public function storeSupplier(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
        ]);

        $supplierId = DB::table('WBO_Suppliers')->insertGetId([
            'name' => $validated['name'],
            'contact_number' => $validated['contact_number'] ?: null,
            'email' => $validated['email'] ?: null,
            'lead_time_days' => $validated['lead_time_days'],
        ]);

        $this->audit(
            $request,
            'SUPPLIER_ADDED',
            "Added supplier {$validated['name']}."
        );

        return response()->json([
            'message' => 'Supplier added successfully.',
            'supplier_id' => $supplierId,
        ], 201);
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', Rule::exists('WBO_Suppliers', 'supplier_id')],
            'product_id' => ['required', 'integer', Rule::exists('WBO_Products', 'product_id')],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['DRAFT', 'ORDERED'])],
        ]);

        $product = DB::table('WBO_Products')
            ->select('product_id', 'supplier_id', 'name')
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($product && $product->supplier_id !== null && (int) $product->supplier_id !== (int) $validated['supplier_id']) {
            throw ValidationException::withMessages([
                'supplier_id' => ['The selected supplier does not match the supplier assigned to this product.'],
            ]);
        }

        $poId = DB::table('WBO_PurchaseOrders')->insertGetId([
            'supplier_id' => $validated['supplier_id'],
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'status' => $validated['status'],
            'created_at' => now(),
            'created_by_user_id' => session('user_id'),
        ]);

        $this->audit(
            $request,
            'PURCHASE_ORDER_CREATED',
            "Created purchase order #{$poId} for {$validated['quantity']} unit(s)."
        );

        return response()->json([
            'message' => 'Purchase order created successfully.',
            'po_id' => $poId,
        ], 201);
    }

}
