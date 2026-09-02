<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/*
 * WBO_ROLE_DASHBOARD_V2
 *
 * Live staff API for Operations, Warehouse, Inventory, and Purchasing.
 *
 * Authorization rules:
 * - Staff read only their own role dashboard.
 * - Super Admin reads role data only through preview=1.
 * - Preview is read-only because all write endpoints authorize the actual role.
 * - Operations Manager is monitoring-only in this API.
 * - Warehouse Admin: receiving / Stock In.
 * - Inventory Controller: Stock In + inventory adjustments.
 * - Purchasing Staff: supplier maintenance + PO preparation/submission.
 * - Purchasing Manager: supplier maintenance + PO creation/approval/order/cancel.
 */
class RoleDashboardController extends Controller
{
    private const LIVE_ROLES = [
        'Operations_Manager',
        'Purchasing_Manager',
        'Purchasing_Staff',
        'Warehouse_Admin',
        'Inventory_Controller',
    ];

    private const LOW_STOCK_THRESHOLD = 10;

    private const OPEN_PO_STATUSES = [
        'DRAFT',
        'PENDING_APPROVAL',
        'APPROVED',
        'ORDERED',
        'PARTIALLY_RECEIVED',
    ];

    public function show(
        Request $request,
        string $role,
        NotificationService $notifications
    ): JsonResponse {
        $isPreview = $this->authorizeRead($request, $role);

        if (!$isPreview) {
            $notifications->syncOperationalAlerts();
        }

        return response()->json(
            $this->dashboardData($role, $isPreview)
        );
    }

    // =========================================================
    // WAREHOUSE / INVENTORY ACTIONS
    // =========================================================

    public function stockIn(
        Request $request,
        NotificationService $notifications
    ): JsonResponse {
        $role = $this->authorizeAction([
            'Warehouse_Admin',
            'Inventory_Controller',
        ]);

        $request->merge([
            'batch_number' =>
                trim((string) $request->input('batch_number', '')),
        ]);

        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('WBO_Products', 'product_id'),
            ],
            'batch_number' => [
                'required',
                'string',
                'max:50',
            ],
            'quantity_received' => [
                'required',
                'integer',
                'min:1',
            ],
            'expiry_date' => [
                'nullable',
                'date',
            ],
        ]);

        $duplicate = DB::table('WBO_Batches')
            ->where('product_id', $validated['product_id'])
            ->where('batch_number', $validated['batch_number'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'batch_number' => [
                    'That batch number already exists for the selected product.',
                ],
            ]);
        }

        $batchId = DB::transaction(
            function () use ($validated, $role) {
                $batchId = DB::table('WBO_Batches')
                    ->insertGetId([
                        'product_id' => $validated['product_id'],
                        'batch_number' => $validated['batch_number'],
                        'quantity_received' => $validated['quantity_received'],
                        'current_quantity' => $validated['quantity_received'],
                        'received_date' => now(),
                        'expiry_date' => $validated['expiry_date'] ?? null,
                    ]);

                DB::table('WBO_Transactions')->insert([
                    'batch_id' => $batchId,
                    'transaction_type' => 'RECEIVE',
                    'quantity_change' => $validated['quantity_received'],
                    'order_id' => null,
                    'purchase_order_id' => null,
                    'reference_note' =>
                        "Manual stock-in by {$this->roleLabel($role)}",
                    'performed_by_user_id' => (int) session('user_id'),
                    'timestamp' => now(),
                ]);

                return $batchId;
            }
        );

        $this->audit(
            $request,
            'STOCK_RECEIVED',
            sprintf(
                '%s stocked in batch %s with %d unit(s).',
                $this->roleLabel($role),
                $validated['batch_number'],
                $validated['quantity_received']
            )
        );

        $notifications->syncOperationalAlerts();

        return response()->json([
            'message' => 'Stock received successfully.',
            'batch_id' => $batchId,
        ], 201);
    }

    public function adjustStock(
        Request $request,
        NotificationService $notifications
    ): JsonResponse {
        $role = $this->authorizeAction([
            'Inventory_Controller',
        ]);

        $request->merge([
            'reference_note' =>
                trim((string) $request->input('reference_note', '')),
        ]);

        $validated = $request->validate([
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('WBO_Batches', 'batch_id'),
            ],
            'quantity_change' => [
                'required',
                'integer',
                'not_in:0',
            ],
            'reference_note' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $batch = DB::table('WBO_Batches')
                ->where('batch_id', $validated['batch_id'])
                ->lockForUpdate()
                ->first();

            if (!$batch) {
                throw ValidationException::withMessages([
                    'batch_id' => [
                        'The selected batch no longer exists.',
                    ],
                ]);
            }

            $newQuantity =
                (int) $batch->current_quantity +
                (int) $validated['quantity_change'];

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => [
                        'The adjustment cannot make stock negative.',
                    ],
                ]);
            }

            DB::table('WBO_Batches')
                ->where('batch_id', $validated['batch_id'])
                ->update([
                    'current_quantity' => $newQuantity,
                ]);

            DB::table('WBO_Transactions')->insert([
                'batch_id' => $validated['batch_id'],
                'transaction_type' => 'ADJUSTMENT',
                'quantity_change' => $validated['quantity_change'],
                'order_id' => null,
                'purchase_order_id' => null,
                'reference_note' => $validated['reference_note'],
                'performed_by_user_id' => (int) session('user_id'),
                'timestamp' => now(),
            ]);

            return [
                'batch_number' => (string) $batch->batch_number,
                'new_quantity' => $newQuantity,
            ];
        });

        $this->audit(
            $request,
            'INVENTORY_ADJUSTED',
            sprintf(
                '%s adjusted batch %s by %+d unit(s). New quantity: %d. Reason: %s',
                $this->roleLabel($role),
                $result['batch_number'],
                $validated['quantity_change'],
                $result['new_quantity'],
                $validated['reference_note']
            )
        );

        $notifications->syncOperationalAlerts();

        return response()->json([
            'message' => 'Inventory adjustment saved.',
            'new_quantity' => $result['new_quantity'],
        ]);
    }

    // =========================================================
    // PURCHASING ACTIONS
    // =========================================================

    public function storeSupplier(Request $request): JsonResponse
    {
        $role = $this->authorizeAction([
            'Purchasing_Manager',
            'Purchasing_Staff',
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'supplier_status' => [
                'nullable',
                Rule::in(['ACTIVE', 'INACTIVE']),
            ],
        ]);

        // Purchasing Staff may add operational supplier records but
        // may not create them already disabled.
        $supplierStatus =
            $role === 'Purchasing_Manager'
                ? ($validated['supplier_status'] ?? 'ACTIVE')
                : 'ACTIVE';

        $supplierId = DB::table('WBO_Suppliers')
            ->insertGetId([
                'name' => trim($validated['name']),
                'contact_number' =>
                    $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'lead_time_days' => $validated['lead_time_days'],
                'supplier_status' => $supplierStatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->audit(
            $request,
            'SUPPLIER_ADDED',
            "{$this->roleLabel($role)} added supplier {$validated['name']}."
        );

        return response()->json([
            'message' => 'Supplier added successfully.',
            'supplier_id' => $supplierId,
        ], 201);
    }

    public function updateSupplier(
        Request $request,
        int $supplierId
    ): JsonResponse {
        $role = $this->authorizeAction([
            'Purchasing_Manager',
            'Purchasing_Staff',
        ]);

        $supplier = DB::table('WBO_Suppliers')
            ->where('supplier_id', $supplierId)
            ->first();

        if (!$supplier) {
            abort(404, 'Supplier not found.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'supplier_status' => [
                'nullable',
                Rule::in(['ACTIVE', 'INACTIVE']),
            ],
        ]);

        // Only Purchasing Manager controls supplier activation state.
        $supplierStatus =
            $role === 'Purchasing_Manager'
                ? ($validated['supplier_status'] ?? $supplier->supplier_status)
                : $supplier->supplier_status;

        DB::table('WBO_Suppliers')
            ->where('supplier_id', $supplierId)
            ->update([
                'name' => trim($validated['name']),
                'contact_number' =>
                    $validated['contact_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'lead_time_days' => $validated['lead_time_days'],
                'supplier_status' => $supplierStatus,
                'updated_at' => now(),
            ]);

        $this->audit(
            $request,
            'SUPPLIER_UPDATED',
            "{$this->roleLabel($role)} updated supplier #{$supplierId} ({$validated['name']})."
        );

        return response()->json([
            'message' => 'Supplier updated successfully.',
        ]);
    }

    public function storePurchaseOrder(
        Request $request
    ): JsonResponse {
        $role = $this->authorizeAction([
            'Purchasing_Manager',
            'Purchasing_Staff',
        ]);

        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('WBO_Suppliers', 'supplier_id'),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('WBO_Products', 'product_id'),
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
            'submit_for_approval' => [
                'nullable',
                'boolean',
            ],
        ]);

        $supplier = DB::table('WBO_Suppliers')
            ->where('supplier_id', $validated['supplier_id'])
            ->first();

        if (
            !$supplier ||
            $supplier->supplier_status !== 'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'supplier_id' => [
                    'Purchase orders require an active supplier.',
                ],
            ]);
        }

        $product = DB::table('WBO_Products')
            ->select(
                'product_id',
                'supplier_id',
                'name',
                'unit_cost'
            )
            ->where('product_id', $validated['product_id'])
            ->first();

        if (
            $product &&
            $product->supplier_id !== null &&
            (int) $product->supplier_id !==
                (int) $validated['supplier_id']
        ) {
            throw ValidationException::withMessages([
                'supplier_id' => [
                    'The selected supplier does not match the supplier assigned to this product.',
                ],
            ]);
        }

        $status =
            !empty($validated['submit_for_approval'])
                ? 'PENDING_APPROVAL'
                : 'DRAFT';

        $result = DB::transaction(
            function () use ($validated, $product, $status) {
                $poNumber =
                    $this->generatePurchaseOrderNumber();

                $poId = DB::table('WBO_PurchaseOrders')
                    ->insertGetId([
                        'po_number' => $poNumber,
                        'supplier_id' => $validated['supplier_id'],
                        'status' => $status,
                        'created_by_user_id' =>
                            (int) session('user_id'),
                        'approved_by_user_id' => null,
                        'created_at' => now(),
                        'approved_at' => null,
                        'ordered_at' => null,
                        'received_at' => null,
                        'cancelled_at' => null,
                    ]);

                DB::table('WBO_PurchaseOrderDetails')
                    ->insert([
                        'po_id' => $poId,
                        'product_id' => $validated['product_id'],
                        'quantity_ordered' => $validated['quantity'],
                        'quantity_received' => 0,
                        'unit_cost' =>
                            $product ? $product->unit_cost : 0,
                    ]);

                return [
                    'po_id' => $poId,
                    'po_number' => $poNumber,
                ];
            }
        );

        $this->audit(
            $request,
            'PURCHASE_ORDER_CREATED',
            sprintf(
                '%s created purchase order %s for %d unit(s) with status %s.',
                $this->roleLabel($role),
                $result['po_number'],
                $validated['quantity'],
                $status
            )
        );

        return response()->json([
            'message' =>
                $status === 'PENDING_APPROVAL'
                    ? 'Purchase order created and submitted for approval.'
                    : 'Purchase order draft created successfully.',
            'po_id' => $result['po_id'],
            'po_number' => $result['po_number'],
            'status' => $status,
        ], 201);
    }

    public function updatePurchaseOrderStatus(
        Request $request,
        int $poId
    ): JsonResponse {
        $role = $this->authorizeAction([
            'Purchasing_Manager',
            'Purchasing_Staff',
        ]);

        $validated = $request->validate([
            'action' => [
                'required',
                Rule::in([
                    'submit',
                    'approve',
                    'order',
                    'cancel',
                ]),
            ],
        ]);

        $purchaseOrder = DB::table('WBO_PurchaseOrders')
            ->where('po_id', $poId)
            ->first();

        if (!$purchaseOrder) {
            abort(404, 'Purchase order not found.');
        }

        $action = $validated['action'];
        $userId = (int) session('user_id');

        if ($action === 'submit') {
            if ($purchaseOrder->status !== 'DRAFT') {
                throw ValidationException::withMessages([
                    'action' => [
                        'Only draft purchase orders can be submitted for approval.',
                    ],
                ]);
            }

            if (
                $role === 'Purchasing_Staff' &&
                (int) $purchaseOrder->created_by_user_id !== $userId
            ) {
                abort(
                    403,
                    'Purchasing Staff may submit only purchase orders they created.'
                );
            }

            DB::table('WBO_PurchaseOrders')
                ->where('po_id', $poId)
                ->update([
                    'status' => 'PENDING_APPROVAL',
                ]);

            $message =
                'Purchase order submitted for approval.';
            $auditAction = 'PURCHASE_ORDER_SUBMITTED';
        } elseif ($action === 'approve') {
            $this->requirePurchasingManager($role);

            if (
                $purchaseOrder->status !==
                'PENDING_APPROVAL'
            ) {
                throw ValidationException::withMessages([
                    'action' => [
                        'Only purchase orders pending approval can be approved.',
                    ],
                ]);
            }

            DB::table('WBO_PurchaseOrders')
                ->where('po_id', $poId)
                ->update([
                    'status' => 'APPROVED',
                    'approved_by_user_id' => $userId,
                    'approved_at' => now(),
                ]);

            $message = 'Purchase order approved.';
            $auditAction = 'PURCHASE_ORDER_APPROVED';
        } elseif ($action === 'order') {
            $this->requirePurchasingManager($role);

            if ($purchaseOrder->status !== 'APPROVED') {
                throw ValidationException::withMessages([
                    'action' => [
                        'Only approved purchase orders can be marked ordered.',
                    ],
                ]);
            }

            DB::table('WBO_PurchaseOrders')
                ->where('po_id', $poId)
                ->update([
                    'status' => 'ORDERED',
                    'ordered_at' => now(),
                ]);

            $message =
                'Purchase order marked as ordered.';
            $auditAction = 'PURCHASE_ORDER_ORDERED';
        } else {
            $this->requirePurchasingManager($role);

            if (
                in_array(
                    $purchaseOrder->status,
                    [
                        'RECEIVED',
                        'CANCELLED',
                        'PARTIALLY_RECEIVED',
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'action' => [
                        'This purchase order can no longer be cancelled.',
                    ],
                ]);
            }

            DB::table('WBO_PurchaseOrders')
                ->where('po_id', $poId)
                ->update([
                    'status' => 'CANCELLED',
                    'cancelled_at' => now(),
                ]);

            $message = 'Purchase order cancelled.';
            $auditAction = 'PURCHASE_ORDER_CANCELLED';
        }

        $this->audit(
            $request,
            $auditAction,
            "{$this->roleLabel($role)} changed purchase order {$purchaseOrder->po_number}: {$message}"
        );

        return response()->json([
            'message' => $message,
        ]);
    }

    // =========================================================
    // AUTHORIZATION
    // =========================================================

    private function authorizeRead(
        Request $request,
        string $role
    ): bool {
        if (!in_array($role, self::LIVE_ROLES, true)) {
            abort(
                404,
                'This role dashboard is not available yet.'
            );
        }

        if (session('logged_in') !== true) {
            abort(401, 'Authentication required.');
        }

        $actualRole = (string) session('role');

        if ($actualRole === $role) {
            return false;
        }

        if (
            $actualRole === 'super_admin' &&
            $request->boolean('preview')
        ) {
            return true;
        }

        abort(
            403,
            'You are not authorized to view this role dashboard.'
        );
    }

    private function authorizeAction(
        array $allowedRoles
    ): string {
        if (session('logged_in') !== true) {
            abort(401, 'Authentication required.');
        }

        $actualRole = (string) session('role');

        if (
            !in_array(
                $actualRole,
                $allowedRoles,
                true
            )
        ) {
            abort(
                403,
                'This action is not allowed for your role.'
            );
        }

        return $actualRole;
    }

    private function requirePurchasingManager(
        string $role
    ): void {
        if ($role !== 'Purchasing_Manager') {
            abort(
                403,
                'This purchase-order decision requires Purchasing Manager access.'
            );
        }
    }

    // =========================================================
    // DATA
    // =========================================================

    private function dashboardData(
        string $role,
        bool $isPreview
    ): array {
        $stockTotals = DB::table('WBO_Batches')
            ->select(
                'product_id',
                DB::raw(
                    'SUM(current_quantity) AS available_stock'
                )
            )
            ->groupBy('product_id');

        $products = DB::table('WBO_Products as p')
            ->leftJoin(
                'WBO_Categories as c',
                'c.category_id',
                '=',
                'p.category_id'
            )
            ->leftJoin(
                'WBO_Suppliers as s',
                's.supplier_id',
                '=',
                'p.supplier_id'
            )
            ->leftJoinSub(
                $stockTotals,
                'stock',
                fn($join) =>
                    $join->on(
                        'stock.product_id',
                        '=',
                        'p.product_id'
                    )
            )
            ->select(
                'p.product_id',
                'p.sku',
                'p.name',
                'p.supplier_id',
                'c.name as category',
                's.name as supplier_name',
                DB::raw(
                    'COALESCE(stock.available_stock, 0) AS available_stock'
                )
            )
            ->orderBy('p.name')
            ->get()
            ->map(function ($product) {
                $product->available_stock =
                    (int) $product->available_stock;
                $product->supplier_id =
                    $product->supplier_id === null
                        ? null
                        : (int) $product->supplier_id;
                $product->category =
                    $product->category ?: 'Uncategorized';
                return $product;
            });

        $suppliers = DB::table('WBO_Suppliers as s')
            ->leftJoin(
                'WBO_Products as p',
                'p.supplier_id',
                '=',
                's.supplier_id'
            )
            ->select(
                's.supplier_id',
                's.name',
                's.contact_number',
                's.email',
                's.address',
                's.lead_time_days',
                's.supplier_status',
                DB::raw(
                    'COUNT(p.product_id) AS product_count'
                )
            )
            ->groupBy(
                's.supplier_id',
                's.name',
                's.contact_number',
                's.email',
                's.address',
                's.lead_time_days',
                's.supplier_status'
            )
            ->orderBy('s.name')
            ->get()
            ->map(function ($supplier) {
                $supplier->lead_time_days =
                    (int) $supplier->lead_time_days;
                $supplier->product_count =
                    (int) $supplier->product_count;
                return $supplier;
            });

        $batches = DB::table('WBO_Batches as b')
            ->join(
                'WBO_Products as p',
                'p.product_id',
                '=',
                'b.product_id'
            )
            ->select(
                'b.batch_id',
                'b.product_id',
                'p.sku',
                'p.name as product_name',
                'b.batch_number',
                'b.quantity_received',
                'b.current_quantity',
                'b.received_date',
                'b.expiry_date'
            )
            ->orderByDesc('b.received_date')
            ->limit(100)
            ->get()
            ->map(function ($batch) {
                $batch->quantity_received =
                    (int) $batch->quantity_received;
                $batch->current_quantity =
                    (int) $batch->current_quantity;
                return $batch;
            });

        $transactions = DB::table('WBO_Transactions as t')
            ->join(
                'WBO_Batches as b',
                'b.batch_id',
                '=',
                't.batch_id'
            )
            ->join(
                'WBO_Products as p',
                'p.product_id',
                '=',
                'b.product_id'
            )
            ->leftJoin(
                'WBO_Users as u',
                'u.user_id',
                '=',
                't.performed_by_user_id'
            )
            ->select(
                't.transaction_id',
                't.batch_id',
                'b.batch_number',
                'p.product_id',
                'p.sku',
                'p.name as product_name',
                't.transaction_type',
                't.quantity_change',
                't.timestamp',
                't.order_id',
                't.purchase_order_id',
                't.reference_note',
                'u.name as performed_by'
            )
            ->orderByDesc('t.timestamp')
            ->limit(100)
            ->get()
            ->map(function ($transaction) {
                $transaction->quantity_change =
                    (int) $transaction->quantity_change;
                return $transaction;
            });

        $purchaseOrders = DB::table('WBO_PurchaseOrders as po')
            ->join(
                'WBO_Suppliers as s',
                's.supplier_id',
                '=',
                'po.supplier_id'
            )
            ->leftJoin(
                'WBO_PurchaseOrderDetails as pod',
                'pod.po_id',
                '=',
                'po.po_id'
            )
            ->leftJoin(
                'WBO_Products as p',
                'p.product_id',
                '=',
                'pod.product_id'
            )
            ->leftJoin(
                'WBO_Users as creator',
                'creator.user_id',
                '=',
                'po.created_by_user_id'
            )
            ->leftJoin(
                'WBO_Users as approver',
                'approver.user_id',
                '=',
                'po.approved_by_user_id'
            )
            ->select(
                'po.po_id',
                'po.po_number',
                'po.supplier_id',
                's.name as supplier_name',
                'pod.po_detail_id',
                DB::raw(
                    'COALESCE(pod.product_id, 0) AS product_id'
                ),
                'p.sku',
                'p.name as product_name',
                DB::raw(
                    'COALESCE(pod.quantity_ordered, 0) AS quantity_ordered'
                ),
                DB::raw(
                    'COALESCE(pod.quantity_received, 0) AS quantity_received'
                ),
                DB::raw(
                    'COALESCE(pod.unit_cost, 0) AS unit_cost'
                ),
                'po.status',
                'po.created_at',
                'po.approved_at',
                'po.ordered_at',
                'po.received_at',
                'po.cancelled_at',
                'po.created_by_user_id',
                'creator.name as created_by',
                'po.approved_by_user_id',
                'approver.name as approved_by'
            )
            ->orderByDesc('po.created_at')
            ->limit(100)
            ->get()
            ->map(function ($po) {
                $po->product_id =
                    (int) $po->product_id;
                $po->quantity_ordered =
                    (int) $po->quantity_ordered;
                $po->quantity_received =
                    (int) $po->quantity_received;
                $po->unit_cost =
                    (float) $po->unit_cost;
                $po->created_by_user_id =
                    (int) $po->created_by_user_id;
                $po->approved_by_user_id =
                    $po->approved_by_user_id === null
                        ? null
                        : (int) $po->approved_by_user_id;
                return $po;
            });

        $orderTotals = DB::table('WBO_OrderDetails')
            ->select(
                'order_id',
                DB::raw(
                    'SUM(quantity * unit_price) AS total_amount'
                ),
                DB::raw(
                    'SUM(quantity) AS total_quantity'
                )
            )
            ->groupBy('order_id');

        $orders = DB::table('WBO_Orders as o')
            ->leftJoinSub(
                $orderTotals,
                'totals',
                fn($join) =>
                    $join->on(
                        'totals.order_id',
                        '=',
                        'o.order_id'
                    )
            )
            ->select(
                'o.order_id',
                'o.order_date',
                'o.status',
                DB::raw(
                    'COALESCE(totals.total_amount, o.total_amount, 0) AS total_amount'
                ),
                DB::raw(
                    'COALESCE(totals.total_quantity, 0) AS total_quantity'
                )
            )
            ->orderByDesc('o.order_date')
            ->limit(50)
            ->get()
            ->map(function ($order) {
                $order->total_amount =
                    (float) $order->total_amount;
                $order->total_quantity =
                    (int) $order->total_quantity;
                return $order;
            });

        $lowStockProducts = $products
            ->filter(
                fn($product) =>
                    $product->available_stock > 0 &&
                    $product->available_stock <=
                        self::LOW_STOCK_THRESHOLD
            )
            ->values();

        $outOfStockProducts = $products
            ->filter(
                fn($product) =>
                    $product->available_stock <= 0
            )
            ->values();

        $reorderNeeds = $products
            ->filter(
                fn($product) =>
                    $product->available_stock <=
                        self::LOW_STOCK_THRESHOLD
            )
            ->sortBy('available_stock')
            ->values();

        $openPurchaseOrders = $purchaseOrders
            ->filter(
                fn($po) =>
                    in_array(
                        $po->status,
                        self::OPEN_PO_STATUSES,
                        true
                    )
            )
            ->unique('po_id')
            ->count();

        $pendingApprovalPos = $purchaseOrders
            ->where(
                'status',
                'PENDING_APPROVAL'
            )
            ->unique('po_id')
            ->count();

        $orderedPos = $purchaseOrders
            ->where('status', 'ORDERED')
            ->unique('po_id')
            ->count();

        $today = now()->startOfDay();

        $stockMovementsToday = $transactions
            ->filter(
                fn($transaction) =>
                    $transaction->timestamp !== null &&
                    Carbon::parse(
                        $transaction->timestamp
                    )->greaterThanOrEqualTo($today)
            )
            ->count();

        $receiptsToday = $transactions
            ->filter(
                fn($transaction) =>
                    $transaction->transaction_type ===
                        'RECEIVE' &&
                    $transaction->timestamp !== null &&
                    Carbon::parse(
                        $transaction->timestamp
                    )->greaterThanOrEqualTo($today)
            )
            ->count();

        $expiryCutoff =
            now()->addDays(30)->endOfDay();

        $expiringBatches = $batches
            ->filter(function ($batch) use ($expiryCutoff) {
                if (
                    !$batch->expiry_date ||
                    $batch->current_quantity <= 0
                ) {
                    return false;
                }

                $expiry =
                    Carbon::parse($batch->expiry_date);

                return
                    $expiry->greaterThanOrEqualTo(
                        now()->startOfDay()
                    ) &&
                    $expiry->lessThanOrEqualTo(
                        $expiryCutoff
                    );
            })
            ->values();

        $metrics = [
            'total_products' =>
                $products->count(),
            'total_stock' =>
                (int) $products->sum('available_stock'),
            'low_stock_items' =>
                $lowStockProducts->count(),
            'out_of_stock' =>
                $outOfStockProducts->count(),
            'open_purchase_orders' =>
                $openPurchaseOrders,
            'pending_approval_pos' =>
                $pendingApprovalPos,
            'ordered_pos' =>
                $orderedPos,
            'active_suppliers' =>
                $suppliers
                    ->where(
                        'supplier_status',
                        'ACTIVE'
                    )
                    ->count(),
            'pending_orders' =>
                $orders
                    ->where('status', 'PENDING')
                    ->count(),
            'stock_movements_today' =>
                $stockMovementsToday,
            'receipts_today' =>
                $receiptsToday,
            'expiring_batches' =>
                $expiringBatches->count(),
            'reorder_needs' =>
                $reorderNeeds->count(),
        ];

        $alerts = collect();

        if ($metrics['out_of_stock'] > 0) {
            $alerts->push([
                'tone' => 'danger',
                'title' => 'Out of Stock',
                'message' =>
                    "{$metrics['out_of_stock']} product(s) currently have no available stock.",
            ]);
        }

        if ($metrics['low_stock_items'] > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Low Stock',
                'message' =>
                    "{$metrics['low_stock_items']} product(s) are at or below the " .
                    self::LOW_STOCK_THRESHOLD .
                    '-unit warning level.',
            ]);
        }

        if ($metrics['pending_approval_pos'] > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Purchase Orders Awaiting Approval',
                'message' =>
                    "{$metrics['pending_approval_pos']} purchase order(s) require Purchasing Manager review.",
            ]);
        }

        if ($metrics['expiring_batches'] > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Expiry Watch',
                'message' =>
                    "{$metrics['expiring_batches']} stocked batch(es) expire within 30 days.",
            ]);
        }

        if ($metrics['open_purchase_orders'] > 0) {
            $alerts->push([
                'tone' => 'info',
                'title' => 'Open Purchase Orders',
                'message' =>
                    "{$metrics['open_purchase_orders']} purchase order(s) remain open.",
            ]);
        }

        if ($metrics['pending_orders'] > 0) {
            $alerts->push([
                'tone' => 'info',
                'title' => 'Pending Sales Orders',
                'message' =>
                    "{$metrics['pending_orders']} customer order(s) are pending.",
            ]);
        }

        return [
            'role' => $role,
            'preview' => $isPreview,
            'current_user_id' =>
                (int) session('user_id'),
            'low_stock_threshold' =>
                self::LOW_STOCK_THRESHOLD,
            'metrics' => $metrics,
            'alerts' => $alerts->values(),
            'products' => $products,
            'suppliers' => $suppliers,
            'reorder_needs' =>
                $reorderNeeds,
            'low_stock_products' =>
                $lowStockProducts,
            'out_of_stock_products' =>
                $outOfStockProducts,
            'batches' => $batches,
            'expiring_batches' =>
                $expiringBatches,
            'transactions' => $transactions,
            'purchase_orders' =>
                $purchaseOrders,
            'orders' => $orders,
        ];
    }

    // =========================================================
    // SUPPORT
    // =========================================================

    private function generatePurchaseOrderNumber(): string
    {
        do {
            $poNumber =
                'PO-' .
                now()->format('Ymd-His') .
                '-' .
                Str::upper(Str::random(4));
        } while (
            DB::table('WBO_PurchaseOrders')
                ->where('po_number', $poNumber)
                ->exists()
        );

        return $poNumber;
    }

    private function audit(
        Request $request,
        string $action,
        string $description
    ): void {
        try {
            DB::table('WBO_AuditLogs')->insert([
                'user_id' =>
                    (int) session('user_id'),
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' =>
                    mb_substr(
                        (string) $request->userAgent(),
                        0,
                        500
                    ),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'Warehouse_Admin' =>
                'Warehouse Admin',
            'Inventory_Controller' =>
                'Inventory Controller',
            'Operations_Manager' =>
                'Operations Manager',
            'Purchasing_Manager' =>
                'Purchasing Manager',
            'Purchasing_Staff' =>
                'Purchasing Staff',
            default => $role,
        };
    }
}