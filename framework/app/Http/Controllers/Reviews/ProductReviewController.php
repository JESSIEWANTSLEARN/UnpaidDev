<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductReviewController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $this->requireTable();

        $reviews = DB::table('WBO_ProductReviews as r')
            ->join('WBO_Products as p', 'p.product_id', '=', 'r.product_id')
            ->join('WBO_Users as u', 'u.user_id', '=', 'r.user_id')
            ->where('r.status', 'VISIBLE')
            ->select('r.review_id','r.product_id','p.name as product_name','p.sku','u.name as customer_name','r.rating','r.title','r.comment','r.verified_purchase','r.created_at')
            ->orderByDesc('r.created_at')->limit(60)->get()
            ->map(function ($r) {
                $r->rating = (int) $r->rating;
                $r->verified_purchase = (bool) $r->verified_purchase;
                $parts = preg_split('/\s+/', trim((string) $r->customer_name)) ?: [];
                $r->customer_name = count($parts) > 1 ? $parts[0] . ' ' . mb_substr($parts[count($parts)-1],0,1) . '.' : ($parts[0] ?? 'Verified Customer');
                return $r;
            });

        return response()->json(['reviews' => $reviews]);
    }

    public function mine(): JsonResponse
    {
        $userId = $this->customer();
        $this->requireTable();

        $mine = DB::table('WBO_ProductReviews as r')
            ->join('WBO_Products as p', 'p.product_id', '=', 'r.product_id')
            ->where('r.user_id', $userId)
            ->select('r.review_id','r.product_id','p.name as product_name','p.sku','r.rating','r.title','r.comment','r.status','r.verified_purchase','r.created_at')
            ->orderByDesc('r.created_at')->get();

        $reviewed = $mine->pluck('product_id')->map(fn($id)=>(int)$id)->all();

        $eligible = DB::table('WBO_Orders as o')
            ->join('WBO_OrderDetails as od','od.order_id','=','o.order_id')
            ->join('WBO_Products as p','p.product_id','=','od.product_id')
            ->where('o.customer_user_id',$userId)
            ->where('o.status','FULFILLED')
            ->when($reviewed !== [], fn($q)=>$q->whereNotIn('p.product_id',$reviewed))
            ->select('p.product_id','p.name','p.sku',DB::raw('MAX(o.order_id) AS order_id'),DB::raw('MAX(o.fulfilled_at) AS fulfilled_at'))
            ->groupBy('p.product_id','p.name','p.sku')
            ->orderByDesc('fulfilled_at')->get();

        return response()->json(['eligible_products'=>$eligible,'my_reviews'=>$mine]);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $this->customer();
        $this->requireTable();

        $v = $request->validate([
            'product_id'=>['required','integer',Rule::exists('WBO_Products','product_id')],
            'rating'=>['required','integer','between:1,5'],
            'title'=>['nullable','string','max:120'],
            'comment'=>['required','string','min:3','max:2000'],
        ]);

        if (DB::table('WBO_ProductReviews')->where('user_id',$userId)->where('product_id',$v['product_id'])->exists()) {
            throw ValidationException::withMessages(['product_id'=>['You already reviewed this product.']]);
        }

        $order = DB::table('WBO_Orders as o')
            ->join('WBO_OrderDetails as od','od.order_id','=','o.order_id')
            ->where('o.customer_user_id',$userId)->where('od.product_id',$v['product_id'])->where('o.status','FULFILLED')
            ->orderByDesc('o.fulfilled_at')->orderByDesc('o.order_id')->select('o.order_id')->first();

        if (!$order) throw ValidationException::withMessages(['product_id'=>['Only fulfilled purchases can be reviewed.']]);

        $id = DB::table('WBO_ProductReviews')->insertGetId([
            'product_id'=>$v['product_id'],'user_id'=>$userId,'order_id'=>$order->order_id,'rating'=>$v['rating'],
            'title'=>trim((string)($v['title'] ?? '')) ?: null,'comment'=>trim($v['comment']),'verified_purchase'=>true,
            'status'=>'VISIBLE','created_at'=>now(),'updated_at'=>now(),
        ]);

        $this->audit($request,$userId,'PRODUCT_REVIEW_CREATED',"Customer created verified product review #{$id}.");
        return response()->json(['message'=>'Thank you. Your verified purchase review is now visible.','review_id'=>$id],201);
    }

    public function adminIndex(): JsonResponse
    {
        $this->admin(); $this->requireTable();
        $reviews = DB::table('WBO_ProductReviews as r')
            ->join('WBO_Products as p','p.product_id','=','r.product_id')
            ->join('WBO_Users as u','u.user_id','=','r.user_id')
            ->leftJoin('WBO_Users as m','m.user_id','=','r.moderated_by_user_id')
            ->select('r.*','p.name as product_name','p.sku','u.name as customer_name','u.email as customer_email','m.name as moderated_by')
            ->orderByDesc('r.created_at')->limit(250)->get();

        $m = DB::table('WBO_ProductReviews')
            ->selectRaw('COUNT(*) AS total_reviews')
            ->selectRaw("SUM(CASE WHEN status='VISIBLE' THEN 1 ELSE 0 END) AS visible_reviews")
            ->selectRaw("SUM(CASE WHEN status='HIDDEN' THEN 1 ELSE 0 END) AS hidden_reviews")
            ->selectRaw("SUM(CASE WHEN status='FLAGGED' THEN 1 ELSE 0 END) AS flagged_reviews")
            ->selectRaw("COALESCE(AVG(CASE WHEN status='VISIBLE' THEN rating END),0) AS average_rating")->first();

        return response()->json(['metrics'=>[
            'total_reviews'=>(int)($m->total_reviews??0),'visible_reviews'=>(int)($m->visible_reviews??0),
            'hidden_reviews'=>(int)($m->hidden_reviews??0),'flagged_reviews'=>(int)($m->flagged_reviews??0),
            'average_rating'=>round((float)($m->average_rating??0),2),
        ],'reviews'=>$reviews]);
    }

    public function moderate(Request $request, int $reviewId): JsonResponse
    {
        $adminId = $this->admin(); $this->requireTable();
        $v = $request->validate(['action'=>['required',Rule::in(['hide','restore','flag'])],'reason'=>['nullable','string','max:255']]);
        if (!DB::table('WBO_ProductReviews')->where('review_id',$reviewId)->exists()) abort(404,'Review not found.');
        $status = match($v['action']) { 'hide'=>'HIDDEN','flag'=>'FLAGGED',default=>'VISIBLE' };
        DB::table('WBO_ProductReviews')->where('review_id',$reviewId)->update([
            'status'=>$status,'moderation_reason'=>$status==='VISIBLE'?null:(trim((string)($v['reason']??''))?:null),
            'moderated_by_user_id'=>$adminId,'moderated_at'=>now(),'updated_at'=>now(),
        ]);
        $this->audit($request,$adminId,'PRODUCT_REVIEW_MODERATED',"Super Admin changed product review #{$reviewId} to {$status}.");
        return response()->json(['message'=>"Review marked {$status}."]);
    }

    public function destroy(Request $request, int $reviewId): JsonResponse
    {
        $adminId = $this->admin(); $this->requireTable();
        if (!DB::table('WBO_ProductReviews')->where('review_id',$reviewId)->exists()) abort(404,'Review not found.');
        DB::table('WBO_ProductReviews')->where('review_id',$reviewId)->delete();
        $this->audit($request,$adminId,'PRODUCT_REVIEW_DELETED',"Super Admin permanently deleted product review #{$reviewId}.");
        return response()->json(['message'=>'Review deleted.']);
    }

    private function customer(): int
    {
        if (session('logged_in') !== true) abort(401,'Authentication required.');
        if (session('role') !== 'System_User') abort(403,'Customer review access denied.');
        return (int) session('user_id');
    }
    private function admin(): int
    {
        if (session('logged_in') !== true) abort(401,'Authentication required.');
        if (session('role') !== 'super_admin') abort(403,'Super Admin access required.');
        return (int) session('user_id');
    }
    private function requireTable(): void
    {
        if (!Schema::hasTable('WBO_ProductReviews')) abort(503,'Product reviews are not initialized. Run the migration first.');
    }
    private function audit(Request $request,int $userId,string $action,string $description): void
    {
        if (!Schema::hasTable('WBO_AuditLogs')) return;
        try { DB::table('WBO_AuditLogs')->insert(['user_id'=>$userId,'action'=>$action,'description'=>$description,'ip_address'=>$request->ip(),'user_agent'=>mb_substr((string)$request->userAgent(),0,500),'created_at'=>now()]); }
        catch (\Throwable $e) { report($e); }
    }
}