<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/*
 * WBO_SALES_ROLE_CONTROLLER_V1
 *
 * Sales workflow:
 * PENDING -> PROCESSING -> FULFILLED
 *      \         \
 *       -> UNFULFILLED / CANCELLED
 *
 * PROCESSING reserves stock using FEFO. A reservation reduces available
 * batch stock and records RESERVE transactions. On fulfillment, those
 * reservations become SALE transactions without deducting stock twice.
 * If a processing order is cancelled/unfulfilled, reserved quantities are
 * returned through positive ADJUSTMENT transactions for a complete history.
 */
class SalesRoleController extends Controller
{
    private const SALES_ROLES = [
        'Sales_Manager',
        'Sales_Staff',
    ];

    private const LOW_STOCK_THRESHOLD = 10;

    public function dashboard(
        Request $request,
        string $role
    ): JsonResponse {
        $isPreview =
            $this->authorizeRead($request, $role);

        return response()->json(
            $this->dashboardData(
                $role,
                $isPreview
            )
        );
    }

    public function updateOrderStatus(
        Request $request,
        int $orderId,
        NotificationService $notifications
    ): JsonResponse {
        $role = $this->authorizeAction();

        $validated = $request->validate([
            'action' => [
                'required',
                Rule::in([
                    'process',
                    'fulfill',
                    'unfulfill',
                    'cancel',
                ]),
            ],
        ]);

        $action = $validated['action'];

        $result = DB::transaction(
            function () use (
                $orderId,
                $action,
                $role
            ) {
                $order =
                    DB::table('WBO_Orders')
                        ->where(
                            'order_id',
                            $orderId
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$order) {
                    abort(404, 'Order not found.');
                }

                if ($action === 'process') {
                    if ($order->status !== 'PENDING') {
                        throw ValidationException::withMessages([
                            'action' => [
                                'Only pending orders can be moved to processing.',
                            ],
                        ]);
                    }

                    $reserved =
                        $this->reserveOrderStock(
                            $orderId
                        );

                    DB::table('WBO_Orders')
                        ->where(
                            'order_id',
                            $orderId
                        )
                        ->update([
                            'status' =>
                                'PROCESSING',
                            'fulfilled_at' =>
                                null,
                            'cancelled_at' =>
                                null,
                        ]);

                    return [
                        'status' =>
                            'PROCESSING',
                        'message' =>
                            "Order #{$orderId} is now processing. {$reserved} unit(s) reserved.",
                        'audit_action' =>
                            'ORDER_PROCESSING',
                    ];
                }

                if ($action === 'fulfill') {
                    if (
                        $order->status !==
                        'PROCESSING'
                    ) {
                        throw ValidationException::withMessages([
                            'action' => [
                                'Only processing orders can be fulfilled.',
                            ],
                        ]);
                    }

                    $salesCount =
                        DB::table(
                            'WBO_Transactions'
                        )
                            ->where(
                                'order_id',
                                $orderId
                            )
                            ->where(
                                'transaction_type',
                                'RESERVE'
                            )
                            ->update([
                                'transaction_type' =>
                                    'SALE',
                                'reference_note' =>
                                    "Fulfilled sale for order #{$orderId}",
                                'timestamp' =>
                                    now(),
                            ]);

                    if ($salesCount <= 0) {
                        throw ValidationException::withMessages([
                            'action' => [
                                'This processing order has no active stock reservation.',
                            ],
                        ]);
                    }

                    DB::table('WBO_Orders')
                        ->where(
                            'order_id',
                            $orderId
                        )
                        ->update([
                            'status' =>
                                'FULFILLED',
                            'fulfilled_at' =>
                                now(),
                            'cancelled_at' =>
                                null,
                        ]);

                    return [
                        'status' =>
                            'FULFILLED',
                        'message' =>
                            "Order #{$orderId} fulfilled successfully.",
                        'audit_action' =>
                            'ORDER_FULFILLED',
                    ];
                }

                if ($action === 'unfulfill') {
                    if (
                        !in_array(
                            $order->status,
                            [
                                'PENDING',
                                'PROCESSING',
                            ],
                            true
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'action' => [
                                'Only pending or processing orders can be marked unfulfilled.',
                            ],
                        ]);
                    }

                    if (
                        $order->status ===
                        'PROCESSING'
                    ) {
                        $this->releaseReservations(
                            $orderId,
                            "Order #{$orderId} marked unfulfilled"
                        );
                    }

                    DB::table('WBO_Orders')
                        ->where(
                            'order_id',
                            $orderId
                        )
                        ->update([
                            'status' =>
                                'UNFULFILLED',
                            'fulfilled_at' =>
                                null,
                            'cancelled_at' =>
                                null,
                        ]);

                    return [
                        'status' =>
                            'UNFULFILLED',
                        'message' =>
                            "Order #{$orderId} marked unfulfilled.",
                        'audit_action' =>
                            'ORDER_UNFULFILLED',
                    ];
                }

                if (
                    !in_array(
                        $order->status,
                        [
                            'PENDING',
                            'PROCESSING',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'action' => [
                            'Only pending or processing orders can be cancelled.',
                        ],
                    ]);
                }

                if (
                    $order->status ===
                    'PROCESSING'
                ) {
                    $this->releaseReservations(
                        $orderId,
                        "Order #{$orderId} cancelled"
                    );
                }

                DB::table('WBO_Orders')
                    ->where(
                        'order_id',
                        $orderId
                    )
                    ->update([
                        'status' =>
                            'CANCELLED',
                        'fulfilled_at' =>
                            null,
                        'cancelled_at' =>
                            now(),
                    ]);

                return [
                    'status' =>
                        'CANCELLED',
                    'message' =>
                        "Order #{$orderId} cancelled.",
                    'audit_action' =>
                        'ORDER_CANCELLED',
                ];
            }
        );

        $this->audit(
            $request,
            $result['audit_action'],
            sprintf(
                '%s changed order #%d to %s.',
                $this->roleLabel($role),
                $orderId,
                $result['status']
            )
        );

        // Stock reservation/release may change operational alert state.
        $notifications->syncOperationalAlerts();

        return response()->json([
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    private function reserveOrderStock(
        int $orderId
    ): int {
        $details =
            DB::table(
                'WBO_OrderDetails as od'
            )
                ->join(
                    'WBO_Products as p',
                    'p.product_id',
                    '=',
                    'od.product_id'
                )
                ->where(
                    'od.order_id',
                    $orderId
                )
                ->select(
                    'od.product_id',
                    'od.quantity',
                    'p.name as product_name'
                )
                ->get();

        if ($details->isEmpty()) {
            throw ValidationException::withMessages([
                'action' => [
                    'This order does not contain any products.',
                ],
            ]);
        }

        $totalReserved = 0;

        foreach ($details as $detail) {
            $remaining =
                (int) $detail->quantity;

            /*
             * FEFO: expiring batches first. Batches without expiry are
             * used after dated batches, with older received stock first.
             */
            $batches =
                DB::table('WBO_Batches')
                    ->where(
                        'product_id',
                        $detail->product_id
                    )
                    ->where(
                        'current_quantity',
                        '>',
                        0
                    )
                    ->orderByRaw(
                        'CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END'
                    )
                    ->orderBy(
                        'expiry_date'
                    )
                    ->orderBy(
                        'received_date'
                    )
                    ->orderBy(
                        'batch_id'
                    )
                    ->lockForUpdate()
                    ->get();

            $available =
                (int) $batches->sum(
                    'current_quantity'
                );

            if ($available < $remaining) {
                throw ValidationException::withMessages([
                    'action' => [
                        "{$detail->product_name} needs {$remaining} unit(s), but only {$available} are currently available.",
                    ],
                ]);
            }

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min(
                    $remaining,
                    (int) $batch->current_quantity
                );

                if ($take <= 0) {
                    continue;
                }

                DB::table('WBO_Batches')
                    ->where(
                        'batch_id',
                        $batch->batch_id
                    )
                    ->update([
                        'current_quantity' =>
                            (int) $batch->current_quantity -
                            $take,
                    ]);

                DB::table(
                    'WBO_Transactions'
                )->insert([
                    'batch_id' =>
                        $batch->batch_id,
                    'transaction_type' =>
                        'RESERVE',
                    'quantity_change' =>
                        -$take,
                    'order_id' =>
                        $orderId,
                    'purchase_order_id' =>
                        null,
                    'reference_note' =>
                        "Reserved for order #{$orderId}",
                    'performed_by_user_id' =>
                        (int) session('user_id'),
                    'timestamp' =>
                        now(),
                ]);

                $remaining -= $take;
                $totalReserved += $take;
            }
        }

        return $totalReserved;
    }

    private function releaseReservations(
        int $orderId,
        string $reason
    ): void {
        $reservations =
            DB::table('WBO_Transactions')
                ->where(
                    'order_id',
                    $orderId
                )
                ->where(
                    'transaction_type',
                    'RESERVE'
                )
                ->orderBy(
                    'transaction_id'
                )
                ->get();

        foreach ($reservations as $reservation) {
            $releaseQuantity =
                abs(
                    (int)
                        $reservation
                            ->quantity_change
                );

            if ($releaseQuantity <= 0) {
                continue;
            }

            $batch =
                DB::table('WBO_Batches')
                    ->where(
                        'batch_id',
                        $reservation->batch_id
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$batch) {
                throw ValidationException::withMessages([
                    'action' => [
                        'A reserved inventory batch no longer exists.',
                    ],
                ]);
            }

            DB::table('WBO_Batches')
                ->where(
                    'batch_id',
                    $reservation->batch_id
                )
                ->update([
                    'current_quantity' =>
                        (int) $batch->current_quantity +
                        $releaseQuantity,
                ]);

            /*
             * WBO_Transactions has no RELEASE enum. ADJUSTMENT records
             * the stock return without deleting the original reservation.
             */
            DB::table(
                'WBO_Transactions'
            )->insert([
                'batch_id' =>
                    $reservation->batch_id,
                'transaction_type' =>
                    'ADJUSTMENT',
                'quantity_change' =>
                    $releaseQuantity,
                'order_id' =>
                    $orderId,
                'purchase_order_id' =>
                    null,
                'reference_note' =>
                    "{$reason}; released reserved stock",
                'performed_by_user_id' =>
                    (int) session('user_id'),
                'timestamp' =>
                    now(),
            ]);
        }
    }

    private function authorizeRead(
        Request $request,
        string $role
    ): bool {
        if (
            !in_array(
                $role,
                self::SALES_ROLES,
                true
            )
        ) {
            abort(
                404,
                'This sales role dashboard does not exist.'
            );
        }

        if (
            session('logged_in') !== true
        ) {
            abort(
                401,
                'Authentication required.'
            );
        }

        $actualRole =
            (string) session('role');

        if ($actualRole === $role) {
            return false;
        }

        if (
            $actualRole ===
                'super_admin' &&
            $request->boolean('preview')
        ) {
            return true;
        }

        abort(
            403,
            'You are not authorized to view this sales dashboard.'
        );
    }

    private function authorizeAction(): string
    {
        if (
            session('logged_in') !== true
        ) {
            abort(
                401,
                'Authentication required.'
            );
        }

        $role =
            (string) session('role');

        if (
            !in_array(
                $role,
                self::SALES_ROLES,
                true
            )
        ) {
            abort(
                403,
                'This sales action is not allowed for your role.'
            );
        }

        return $role;
    }

    private function dashboardData(
        string $role,
        bool $isPreview
    ): array {
        $stockTotals =
            DB::table('WBO_Batches')
                ->select(
                    'product_id',
                    DB::raw(
                        'SUM(current_quantity) AS available_stock'
                    )
                )
                ->groupBy(
                    'product_id'
                );

        $products =
            DB::table('WBO_Products as p')
                ->leftJoin(
                    'WBO_Categories as c',
                    'c.category_id',
                    '=',
                    'p.category_id'
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
                    'c.name as category',
                    'p.unit_price',
                    DB::raw(
                        'COALESCE(stock.available_stock, 0) AS available_stock'
                    )
                )
                ->orderBy(
                    'p.name'
                )
                ->get()
                ->map(function ($product) {
                    $product
                        ->available_stock =
                        (int)
                        $product
                            ->available_stock;
                    $product->unit_price =
                        (float)
                        $product->unit_price;
                    $product->category =
                        $product->category ?:
                        'Uncategorized';

                    return $product;
                });

        $details =
            DB::table(
                'WBO_OrderDetails as od'
            )
                ->join(
                    'WBO_Products as p',
                    'p.product_id',
                    '=',
                    'od.product_id'
                )
                ->select(
                    'od.order_detail_id',
                    'od.order_id',
                    'od.product_id',
                    'p.sku',
                    'p.name as product_name',
                    'od.quantity',
                    'od.unit_price'
                )
                ->orderBy(
                    'od.order_detail_id'
                )
                ->get()
                ->groupBy(
                    'order_id'
                );

        $orders =
            DB::table('WBO_Orders as o')
                ->leftJoin(
                    'WBO_Users as u',
                    'u.user_id',
                    '=',
                    'o.customer_user_id'
                )
                ->select(
                    'o.order_id',
                    'o.customer_user_id',
                    'o.customer_name',
                    'o.customer_contact',
                    'u.email as customer_email',
                    'o.order_date',
                    'o.status',
                    'o.total_amount',
                    'o.fulfilled_at',
                    'o.cancelled_at'
                )
                ->orderByDesc(
                    'o.order_date'
                )
                ->limit(150)
                ->get()
                ->map(
                    function ($order) use (
                        $details
                    ) {
                        $items =
                            collect(
                                $details->get(
                                    $order->order_id,
                                    []
                                )
                            )
                                ->map(
                                    function (
                                        $item
                                    ) {
                                        return [
                                            'order_detail_id' =>
                                                $item
                                                    ->order_detail_id,
                                            'product_id' =>
                                                $item
                                                    ->product_id,
                                            'sku' =>
                                                $item->sku,
                                            'product_name' =>
                                                $item
                                                    ->product_name,
                                            'quantity' =>
                                                (int)
                                                $item
                                                    ->quantity,
                                            'unit_price' =>
                                                (float)
                                                $item
                                                    ->unit_price,
                                            'line_total' =>
                                                (float)
                                                $item
                                                    ->unit_price *
                                                (int)
                                                $item
                                                    ->quantity,
                                        ];
                                    }
                                )
                                ->values();

                        $calculatedTotal =
                            (float)
                            $items->sum(
                                'line_total'
                            );

                        return [
                            'order_id' =>
                                (int)
                                $order->order_id,
                            'customer_user_id' =>
                                (int)
                                $order
                                    ->customer_user_id,
                            'customer_name' =>
                                $order
                                    ->customer_name,
                            'customer_contact' =>
                                $order
                                    ->customer_contact,
                            'customer_email' =>
                                $order
                                    ->customer_email,
                            'order_date' =>
                                $order
                                    ->order_date,
                            'status' =>
                                $order->status,
                            'total_amount' =>
                                $calculatedTotal > 0
                                    ? $calculatedTotal
                                    : (float)
                                      $order
                                          ->total_amount,
                            'total_quantity' =>
                                (int)
                                $items->sum(
                                    'quantity'
                                ),
                            'fulfilled_at' =>
                                $order
                                    ->fulfilled_at,
                            'cancelled_at' =>
                                $order
                                    ->cancelled_at,
                            'items' =>
                                $items,
                        ];
                    }
                );

        $customers =
            DB::table('WBO_Users as u')
                ->leftJoin(
                    'WBO_Orders as o',
                    function ($join) {
                        $join
                            ->on(
                                'o.customer_user_id',
                                '=',
                                'u.user_id'
                            );
                    }
                )
                ->leftJoin(
                    'WBO_OrderDetails as od',
                    'od.order_id',
                    '=',
                    'o.order_id'
                )
                ->where(
                    'u.role',
                    'System_User'
                )
                ->select(
                    'u.user_id',
                    'u.name',
                    'u.email',
                    'u.contact_number',
                    'u.account_status',
                    DB::raw(
                        'COUNT(DISTINCT o.order_id) AS order_count'
                    ),
                    DB::raw(
                        "COUNT(DISTINCT CASE WHEN o.status = 'FULFILLED' THEN o.order_id END) AS fulfilled_orders"
                    ),
                    DB::raw(
                        "COALESCE(SUM(CASE WHEN o.status = 'FULFILLED' THEN od.quantity * od.unit_price ELSE 0 END), 0) AS total_spent"
                    ),
                    DB::raw(
                        'MAX(o.order_date) AS last_order_at'
                    )
                )
                ->groupBy(
                    'u.user_id',
                    'u.name',
                    'u.email',
                    'u.contact_number',
                    'u.account_status'
                )
                ->orderByDesc(
                    'last_order_at'
                )
                ->get()
                ->map(function ($customer) {
                    $customer->order_count =
                        (int)
                        $customer->order_count;
                    $customer
                        ->fulfilled_orders =
                        (int)
                        $customer
                            ->fulfilled_orders;
                    $customer->total_spent =
                        (float)
                        $customer->total_spent;

                    return $customer;
                });

        $productPerformance =
            DB::table(
                'WBO_OrderDetails as od'
            )
                ->join(
                    'WBO_Orders as o',
                    'o.order_id',
                    '=',
                    'od.order_id'
                )
                ->join(
                    'WBO_Products as p',
                    'p.product_id',
                    '=',
                    'od.product_id'
                )
                ->where(
                    'o.status',
                    'FULFILLED'
                )
                ->select(
                    'p.product_id',
                    'p.sku',
                    'p.name',
                    DB::raw(
                        'SUM(od.quantity) AS units_sold'
                    ),
                    DB::raw(
                        'SUM(od.quantity * od.unit_price) AS revenue'
                    ),
                    DB::raw(
                        'COUNT(DISTINCT o.order_id) AS order_count'
                    )
                )
                ->groupBy(
                    'p.product_id',
                    'p.sku',
                    'p.name'
                )
                ->orderByDesc(
                    'units_sold'
                )
                ->get()
                ->map(function ($row) {
                    $row->units_sold =
                        (int)
                        $row->units_sold;
                    $row->revenue =
                        (float)
                        $row->revenue;
                    $row->order_count =
                        (int)
                        $row->order_count;

                    return $row;
                });

        $monthStart =
            now()->startOfMonth();

        $monthlyRevenue =
            DB::table(
                'WBO_Orders as o'
            )
                ->join(
                    'WBO_OrderDetails as od',
                    'od.order_id',
                    '=',
                    'o.order_id'
                )
                ->where(
                    'o.status',
                    'FULFILLED'
                )
                ->where(
                    'o.order_date',
                    '>=',
                    $monthStart
                )
                ->selectRaw(
                    'COALESCE(SUM(od.quantity * od.unit_price), 0) AS total'
                )
                ->value('total');

        $monthlyFulfilled =
            DB::table('WBO_Orders')
                ->where(
                    'status',
                    'FULFILLED'
                )
                ->where(
                    'order_date',
                    '>=',
                    $monthStart
                )
                ->count();

        $lowStock =
            $products
                ->filter(
                    fn($product) =>
                        $product
                            ->available_stock <=
                        self::LOW_STOCK_THRESHOLD
                )
                ->values();

        $metrics = [
            'total_orders' =>
                $orders->count(),
            'pending_orders' =>
                $orders
                    ->where(
                        'status',
                        'PENDING'
                    )
                    ->count(),
            'processing_orders' =>
                $orders
                    ->where(
                        'status',
                        'PROCESSING'
                    )
                    ->count(),
            'fulfilled_orders' =>
                $orders
                    ->where(
                        'status',
                        'FULFILLED'
                    )
                    ->count(),
            'unfulfilled_orders' =>
                $orders
                    ->where(
                        'status',
                        'UNFULFILLED'
                    )
                    ->count(),
            'cancelled_orders' =>
                $orders
                    ->where(
                        'status',
                        'CANCELLED'
                    )
                    ->count(),
            'monthly_revenue' =>
                (float)
                ($monthlyRevenue ?? 0),
            'monthly_fulfilled' =>
                $monthlyFulfilled,
            'customers' =>
                $customers->count(),
            'low_stock_products' =>
                $lowStock->count(),
        ];

        $alerts = collect();

        if (
            $metrics['pending_orders'] > 0
        ) {
            $alerts->push([
                'tone' => 'warning',
                'title' =>
                    'Orders Awaiting Review',
                'message' =>
                    "{$metrics['pending_orders']} order(s) are still pending Sales Staff review.",
            ]);
        }

        if (
            $metrics[
                'unfulfilled_orders'
            ] > 0
        ) {
            $alerts->push([
                'tone' => 'danger',
                'title' =>
                    'Unfulfilled Orders',
                'message' =>
                    "{$metrics['unfulfilled_orders']} order(s) are currently marked unfulfilled.",
            ]);
        }

        if (
            $metrics[
                'low_stock_products'
            ] > 0
        ) {
            $alerts->push([
                'tone' => 'warning',
                'title' =>
                    'Fulfillment Stock Risk',
                'message' =>
                    "{$metrics['low_stock_products']} product(s) are at or below the " .
                    self::LOW_STOCK_THRESHOLD .
                    '-unit stock warning level.',
            ]);
        }

        if (
            $metrics[
                'processing_orders'
            ] > 0
        ) {
            $alerts->push([
                'tone' => 'info',
                'title' =>
                    'Orders in Processing',
                'message' =>
                    "{$metrics['processing_orders']} order(s) currently have stock reserved for fulfillment.",
            ]);
        }

        return [
            'role' => $role,
            'preview' => $isPreview,
            'low_stock_threshold' =>
                self::LOW_STOCK_THRESHOLD,
            'metrics' => $metrics,
            'alerts' =>
                $alerts->values(),
            'orders' => $orders,
            'customers' => $customers,
            'products' => $products,
            'product_performance' =>
                $productPerformance,
            'low_stock_products' =>
                $lowStock,
        ];
    }

    private function audit(
        Request $request,
        string $action,
        string $description
    ): void {
        try {
            DB::table(
                'WBO_AuditLogs'
            )->insert([
                'user_id' =>
                    (int)
                    session('user_id'),
                'action' =>
                    $action,
                'description' =>
                    $description,
                'ip_address' =>
                    $request->ip(),
                'user_agent' =>
                    mb_substr(
                        (string)
                        $request
                            ->userAgent(),
                        0,
                        500
                    ),
                'created_at' =>
                    now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function roleLabel(
        string $role
    ): string {
        return match ($role) {
            'Sales_Manager' =>
                'Sales Manager',
            'Sales_Staff' =>
                'Sales Staff',
            default =>
                $role,
        };
    }
}