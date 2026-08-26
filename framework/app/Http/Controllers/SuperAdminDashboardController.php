<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSuperAdminSupport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminDashboardController extends Controller
{
    use HandlesSuperAdminSupport;
    private const LOW_STOCK_THRESHOLD = 10;

    public function index(): JsonResponse
    {
        $this->authorizeSuperAdmin();

        return response()->json($this->dashboardData());
    }


    public function exportReport(Request $request): StreamedResponse|JsonResponse
    {
        $this->authorizeSuperAdmin();

        $data = $this->dashboardData();

        $this->audit($request, 'REPORT_EXPORTED', 'Exported the Super Admin system report.');

        $fileName = 'WalangBrownout-SuperAdmin-Report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['WalangBrownout Super Admin Report']);
            fputcsv($output, ['Generated At', now()->format('Y-m-d H:i:s')]);
            fputcsv($output, []);

            fputcsv($output, ['SUMMARY']);
            foreach ($data['metrics'] as $key => $value) {
                fputcsv($output, [Str::headline($key), $value]);
            }

            fputcsv($output, []);
            fputcsv($output, ['PRODUCTS']);
            fputcsv($output, ['Product ID', 'SKU', 'Name', 'Category', 'ABC Class', 'Unit Cost', 'Unit Price', 'Stock', 'Visible', 'Featured']);
            foreach ($data['products'] as $product) {
                fputcsv($output, [
                    $product->product_id,
                    $product->sku,
                    $product->name,
                    $product->category,
                    $product->abc_class,
                    $product->unit_cost,
                    $product->unit_price,
                    $product->available_stock,
                    $product->is_visible ? 'Yes' : 'No',
                    $product->is_featured ? 'Yes' : 'No',
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['SUPPLIERS']);
            fputcsv($output, ['Supplier ID', 'Name', 'Contact', 'Email', 'Lead Time Days', 'Products']);
            foreach ($data['suppliers'] as $supplier) {
                fputcsv($output, [
                    $supplier->supplier_id,
                    $supplier->name,
                    $supplier->contact_number,
                    $supplier->email,
                    $supplier->lead_time_days,
                    $supplier->product_count,
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['SALES ORDERS']);
            fputcsv($output, ['Order ID', 'Customer User ID', 'Customer', 'Contact', 'Order Date', 'Status', 'Items', 'Total']);
            foreach ($data['orders'] as $order) {
                fputcsv($output, [
                    $order->order_id,
                    $order->customer_user_id,
                    $order->customer_name,
                    $order->customer_contact,
                    $order->order_date,
                    $order->status,
                    $order->total_quantity,
                    $order->total_amount,
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['USERS']);
            fputcsv($output, ['User ID', 'Name', 'Email', 'Contact', 'Role', 'Status', 'Presence', 'Last Seen', 'Verified At', 'Created At']);
            foreach ($data['users'] as $user) {
                fputcsv($output, [
                    $user->user_id,
                    $user->name,
                    $user->email,
                    $user->contact_number,
                    $user->role,
                    $user->account_status,
                    $user->is_online ? 'ONLINE' : 'OFFLINE',
                    $user->last_seen_at,
                    $user->email_verified_at,
                    $user->created_at,
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['AUDIT LOGS']);
            fputcsv($output, ['Log ID', 'User', 'Action', 'Description', 'IP Address', 'Created At']);
            foreach ($data['audit_logs'] as $log) {
                fputcsv($output, [
                    $log->log_id,
                    $log->user_name ?: 'System',
                    $log->action,
                    $log->description,
                    $log->ip_address,
                    $log->created_at,
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }


    private function dashboardData(): array
    {
        $currentUser = DB::table('WBO_Users')
            ->select(
                'user_id',
                'name',
                'email',
                'contact_number',
                'role',
                'account_status',
                'email_verified_at',
                'created_at'
            )
            ->where('user_id', session('user_id'))
            ->first();

        if (!$currentUser) {
            session()->invalidate();
            abort(401, 'The signed-in user no longer exists.');
        }

        $stockTotals = DB::table('WBO_Batches')
            ->select('product_id', DB::raw('SUM(current_quantity) AS available_stock'))
            ->groupBy('product_id');

        $primaryImages = DB::table('WBO_ProductImages')
            ->select(
                'product_id',
                DB::raw('MAX(CASE WHEN is_primary = 1 THEN image_path END) AS image_path')
            )
            ->groupBy('product_id');

        $products = DB::table('WBO_Products as p')
            ->leftJoinSub($stockTotals, 'stock', function ($join) {
                $join->on('stock.product_id', '=', 'p.product_id');
            })
            ->leftJoinSub($primaryImages, 'images', function ($join) {
                $join->on('images.product_id', '=', 'p.product_id');
            })
            ->select(
                'p.product_id',
                'p.sku',
                'p.name',
                'p.description',
                'p.category',
                'p.supplier_id',
                'p.abc_class',
                'p.is_seasonal',
                'p.is_visible',
                'p.is_featured',
                'p.unit_cost',
                'p.unit_price',
                'p.created_at',
                'p.updated_at',
                DB::raw('COALESCE(stock.available_stock, 0) AS available_stock'),
                'images.image_path'
            )
            ->orderBy('p.product_id')
            ->get()
            ->map(function ($product) {
                $product->available_stock = (int) $product->available_stock;
                $product->unit_cost = (float) $product->unit_cost;
                $product->unit_price = (float) $product->unit_price;
                $product->is_seasonal = (bool) $product->is_seasonal;
                $product->is_visible = (bool) $product->is_visible;
                $product->is_featured = (bool) $product->is_featured;
                $product->category = $product->category ?: 'Uncategorized';
                $product->image_url = $product->image_path
                    ? Storage::url($product->image_path)
                    : null;
                unset($product->image_path);

                return $product;
            });

        $batches = DB::table('WBO_Batches as b')
            ->join('WBO_Products as p', 'p.product_id', '=', 'b.product_id')
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
            ->get();

        $suppliers = DB::table('WBO_Suppliers as s')
            ->leftJoin('WBO_Products as p', 'p.supplier_id', '=', 's.supplier_id')
            ->select(
                's.supplier_id',
                's.name',
                's.contact_number',
                's.email',
                's.lead_time_days',
                DB::raw('COUNT(p.product_id) AS product_count')
            )
            ->groupBy(
                's.supplier_id',
                's.name',
                's.contact_number',
                's.email',
                's.lead_time_days'
            )
            ->orderBy('s.name')
            ->get();

        $orderTotals = DB::table('WBO_OrderDetails')
            ->select(
                'order_id',
                DB::raw('SUM(quantity * unit_price) AS total_amount'),
                DB::raw('SUM(quantity) AS total_quantity')
            )
            ->groupBy('order_id');

        $orders = DB::table('WBO_Orders as o')
            ->leftJoinSub($orderTotals, 'totals', function ($join) {
                $join->on('totals.order_id', '=', 'o.order_id');
            })
            ->select(
                'o.order_id',
                'o.customer_user_id',
                'o.customer_name',
                'o.customer_contact',
                'o.order_date',
                'o.status',
                DB::raw('COALESCE(totals.total_amount, 0) AS total_amount'),
                DB::raw('COALESCE(totals.total_quantity, 0) AS total_quantity')
            )
            ->orderByDesc('o.order_date')
            ->get()
            ->map(function ($order) {
                $order->total_amount = (float) $order->total_amount;
                $order->total_quantity = (int) $order->total_quantity;
                return $order;
            });

        $transactions = DB::table('WBO_Transactions as t')
            ->join('WBO_Batches as b', 'b.batch_id', '=', 't.batch_id')
            ->join('WBO_Products as p', 'p.product_id', '=', 'b.product_id')
            ->leftJoin('WBO_Users as u', 'u.user_id', '=', 't.performed_by_user_id')
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
                't.performed_by_user_id',
                'u.name as performed_by'
            )
            ->orderByDesc('t.timestamp')
            ->limit(100)
            ->get();

        $purchaseOrders = DB::table('WBO_PurchaseOrders as po')
            ->join('WBO_Suppliers as s', 's.supplier_id', '=', 'po.supplier_id')
            ->join('WBO_Products as p', 'p.product_id', '=', 'po.product_id')
            ->leftJoin('WBO_Users as u', 'u.user_id', '=', 'po.created_by_user_id')
            ->select(
                'po.po_id',
                'po.supplier_id',
                's.name as supplier_name',
                'po.product_id',
                'p.sku',
                'p.name as product_name',
                'po.quantity',
                'po.status',
                'po.created_at',
                'po.created_by_user_id',
                'u.name as created_by'
            )
            ->orderByDesc('po.created_at')
            ->get();

        $onlineCutoff = now()->subMinutes(5);

        $users = DB::table('WBO_Users')
            ->select(
                'user_id',
                'name',
                'email',
                'contact_number',
                'role',
                'account_status',
                'last_seen_at',
                'email_verified_at',
                'created_at'
            )
            ->orderBy('user_id')
            ->get()
            ->map(function ($user) use ($onlineCutoff) {
                $user->is_online = $user->account_status === 'active'
                    && $user->last_seen_at !== null
                    && \Carbon\Carbon::parse($user->last_seen_at)->greaterThanOrEqualTo($onlineCutoff);

                return $user;
            });

        $notifications = DB::table('WBO_Notifications as n')
            ->leftJoin('WBO_Products as p', 'p.product_id', '=', 'n.related_product_id')
            ->leftJoin('WBO_Batches as b', 'b.batch_id', '=', 'n.related_batch_id')
            ->leftJoin('WBO_Users as u', 'u.user_id', '=', 'n.recipient_user_id')
            ->select(
                'n.notification_id',
                'n.alert_tier',
                'n.related_product_id',
                'p.name as product_name',
                'n.related_batch_id',
                'b.batch_number',
                'n.recipient_user_id',
                'u.name as recipient_name',
                'n.triggered_at',
                'n.status',
                'n.resolved_at'
            )
            ->orderByDesc('n.triggered_at')
            ->limit(50)
            ->get();

        $auditLogs = DB::table('WBO_AuditLogs as a')
            ->leftJoin('WBO_Users as u', 'u.user_id', '=', 'a.user_id')
            ->select(
                'a.log_id',
                'a.user_id',
                'u.name as user_name',
                'a.action',
                'a.description',
                'a.ip_address',
                'a.created_at'
            )
            ->orderByDesc('a.created_at')
            ->limit(100)
            ->get();

        $categories = $products
            ->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'category' => $category,
                    'product_count' => $items->count(),
                    'total_stock' => $items->sum('available_stock'),
                ];
            })
            ->values();

        $monthStart = now()->startOfMonth();

        $monthlyRevenue = DB::table('WBO_Orders as o')
            ->join('WBO_OrderDetails as od', 'od.order_id', '=', 'o.order_id')
            ->where('o.status', 'FULFILLED')
            ->where('o.order_date', '>=', $monthStart)
            ->selectRaw('COALESCE(SUM(od.quantity * od.unit_price), 0) AS total')
            ->value('total');

        $monthlyOrders = DB::table('WBO_Orders')
            ->where('order_date', '>=', $monthStart)
            ->count();

        $productsSold = DB::table('WBO_Orders as o')
            ->join('WBO_OrderDetails as od', 'od.order_id', '=', 'o.order_id')
            ->where('o.status', 'FULFILLED')
            ->where('o.order_date', '>=', $monthStart)
            ->sum('od.quantity');

        $trendStart = now()->startOfWeek()->subWeeks(5);

        $trendTransactions = DB::table('WBO_Transactions')
            ->select('quantity_change', 'timestamp')
            ->where('timestamp', '>=', $trendStart)
            ->orderBy('timestamp')
            ->get();

        $stockTrend = collect(range(0, 5))->map(function ($weekOffset) use ($trendStart, $trendTransactions) {
            $weekStart = Carbon::parse($trendStart)->copy()->addWeeks($weekOffset);
            $weekEnd = $weekStart->copy()->endOfWeek();

            $netMovement = $trendTransactions
                ->filter(function ($transaction) use ($weekStart, $weekEnd) {
                    $timestamp = Carbon::parse($transaction->timestamp);
                    return $timestamp->betweenIncluded($weekStart, $weekEnd);
                })
                ->sum('quantity_change');

            return [
                'label' => $weekStart->format('M d'),
                'net_movement' => (int) $netMovement,
            ];
        })->values();

        $metrics = [
            'total_products' => $products->count(),
            'total_stock' => (int) $products->sum('available_stock'),
            'low_stock_items' => $products
                ->filter(fn ($product) => $product->available_stock > 0
                    && $product->available_stock <= self::LOW_STOCK_THRESHOLD)
                ->count(),
            'out_of_stock' => $products
                ->filter(fn ($product) => $product->available_stock <= 0)
                ->count(),
            'total_suppliers' => $suppliers->count(),
            'pending_orders' => $orders->where('status', 'PENDING')->count(),
            'total_users' => $users->count(),
            'monthly_revenue' => (float) $monthlyRevenue,
            'monthly_orders' => (int) $monthlyOrders,
            'products_sold' => (int) $productsSold,
            'unread_notifications' => $notifications->where('status', 'UNREAD')->count(),
            'open_purchase_orders' => $purchaseOrders
                ->whereIn('status', ['DRAFT', 'ORDERED'])
                ->count(),
        ];

        return [
            'current_user' => $currentUser,
            'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            'metrics' => $metrics,
            'products' => $products,
            'batches' => $batches,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'orders' => $orders,
            'transactions' => $transactions,
            'purchase_orders' => $purchaseOrders,
            'users' => $users,
            'notifications' => $notifications,
            'audit_logs' => $auditLogs,
            'stock_trend' => $stockTrend,
            'settings' => $this->systemSettings(),
            'backups' => $this->backupList(),
        ];
    }

}
