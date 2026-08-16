<?php

namespace App\Http\Controllers;

use App\Models\WBOUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SystemUserController extends Controller
{
    private function currentUser(Request $request): WBOUser
    {
        if (
            !$request->session()->get('logged_in') ||
            !$request->session()->get('user_id')
        ) {
            abort(401, 'Please log in first.');
        }

        if ($request->session()->get('role') !== 'System_User') {
            abort(403, 'This page is only available to System Users.');
        }

        $user = WBOUser::find(
            $request->session()->get('user_id')
        );

        if (
            !$user ||
            $user->account_status !== 'active'
        ) {
            $request->session()->invalidate();

            abort(
                401,
                'Your account is unavailable.'
            );
        }

        return $user;
    }

    // all the other methods continue here...

    private function audit(Request $request, int $userId, string $action, ?string $description = null): void
    {
        try {
            DB::table('WBO_AuditLogs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function me(Request $request)
    {
        $user = $this->currentUser($request);

        $stats = DB::table('WBO_Orders')
            ->where('customer_user_id', $user->user_id)
            ->selectRaw('COUNT(*) AS total_orders')
            ->selectRaw("SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_orders")
            ->selectRaw("SUM(CASE WHEN status = 'FULFILLED' THEN 1 ELSE 0 END) AS fulfilled_orders")
            ->first();

        return response()->json([
            'success' => true,
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'role' => $user->role,
                'account_status' => $user->account_status,
            ],
            'stats' => [
                'total_orders' => (int) ($stats->total_orders ?? 0),
                'pending_orders' => (int) ($stats->pending_orders ?? 0),
                'fulfilled_orders' => (int) ($stats->fulfilled_orders ?? 0),
            ],
        ]);
    }

    public function orders(Request $request)
    {
        $user = $this->currentUser($request);

        $orders = DB::table('WBO_Orders')
            ->where('customer_user_id', $user->user_id)
            ->orderByDesc('order_date')
            ->get();

        $ids = $orders->pluck('order_id')->all();
        $details = collect();

        if ($ids) {
            $details = DB::table('WBO_OrderDetails as od')
                ->join('WBO_Products as p', 'p.product_id', '=', 'od.product_id')
                ->whereIn('od.order_id', $ids)
                ->select('od.*', 'p.name as product_name', 'p.sku')
                ->get()
                ->groupBy('order_id');
        }

        $payload = $orders->map(function ($order) use ($details) {
            $items = collect($details->get($order->order_id, []))->map(function ($item) {
                return [
                    'order_detail_id' => $item->order_detail_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->unit_price * (int) $item->quantity,
                ];
            })->values();

            return [
                'order_id' => $order->order_id,
                'order_date' => $order->order_date,
                'status' => $order->status,
                'items' => $items,
                'total' => (float) $items->sum('line_total'),
            ];
        });

        return response()->json(['success' => true, 'orders' => $payload]);
    }

    public function placeOrder(Request $request)
    {
        $user = $this->currentUser($request);

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your order.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = collect($validator->validated()['items'])
            ->groupBy('product_id')
            ->map(fn ($rows, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => (int) collect($rows)->sum('quantity'),
            ])->values();

        try {
            $orderId = DB::transaction(function () use ($items, $user, $request) {
                $prepared = [];

                foreach ($items as $item) {
                    $product = DB::table('WBO_Products')
                        ->where('product_id', $item['product_id'])
                        ->where('is_visible', true)
                        ->first();

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'items' => ['One selected product is no longer available.'],
                        ]);
                    }

                    $stock = (int) DB::table('WBO_Batches')
                        ->where('product_id', $product->product_id)
                        ->sum('current_quantity');

                    if ($item['quantity'] > $stock) {
                        throw ValidationException::withMessages([
                            'items' => ["{$product->name} only has {$stock} unit(s) available."],
                        ]);
                    }

                    $prepared[] = [
                        'product_id' => $product->product_id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->unit_price,
                    ];
                }

                $orderId = DB::table('WBO_Orders')->insertGetId([
                    'customer_user_id' => $user->user_id,
                    'customer_name' => $user->name,
                    'customer_contact' => $user->contact_number,
                    'order_date' => now(),
                    'status' => 'PENDING',
                ]);

                foreach ($prepared as $item) {
                    DB::table('WBO_OrderDetails')->insert([
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]);
                }

                $this->audit($request, $user->user_id, 'ORDER_CREATED', "Customer placed order #{$orderId}");

                return $orderId;
            });

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully. Waiting for Sales Staff review.',
                'order_id' => $orderId,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
        ]);

        $user->name = trim($validated['name']);
        $user->contact_number = $validated['contact_number'] ? trim($validated['contact_number']) : null;
        $user->save();

        $request->session()->put('name', $user->name);
        $this->audit($request, $user->user_id, 'PROFILE_UPDATED', 'System User updated account profile');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'role' => $user->role,
            ],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->password_hash = Hash::make($validated['password']);
        $user->save();

        $this->audit($request, $user->user_id, 'PASSWORD_CHANGED', 'System User changed account password');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }
}
