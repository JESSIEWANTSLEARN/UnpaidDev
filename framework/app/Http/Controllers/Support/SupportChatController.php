<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\WBOUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupportChatController extends Controller
{
    private const STAFF_ROLES = ['Sales_Manager', 'Sales_Staff'];

    public function customerIndex(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $this->ready();

        $rows = DB::table('WBO_Conversations')
            ->where('customer_user_id', $user->user_id)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get()
            ->map(fn ($row) => $this->summary($row))
            ->values();

        return response()->json(['success' => true, 'conversations' => $rows]);
    }

    public function customerStart(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $this->ready();

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:150'],
        ]);

        $existing = DB::table('WBO_Conversations')
            ->where('customer_user_id', $user->user_id)
            ->where('status', '<>', 'CLOSED')
            ->orderByDesc('updated_at')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'conversation' => $this->payload((int) $existing->conversation_id),
                'message' => 'Existing support conversation opened.',
            ]);
        }

        $id = DB::transaction(function () use ($user, $validated) {
            $id = DB::table('WBO_Conversations')->insertGetId([
                'customer_user_id' => $user->user_id,
                'assigned_user_id' => null,
                'subject' => trim((string) ($validated['subject'] ?? '')) ?: 'Customer support',
                'status' => 'BOT',
                'created_at' => now(),
                'updated_at' => now(),
                'closed_at' => null,
            ]);

            $this->message(
                (int) $id,
                null,
                'BOT',
                'Hi! Ask a question and I will search the Walang Brownout FAQ. You can also choose Talk to staff.'
            );

            return (int) $id;
        });

        $this->audit($request, (int) $user->user_id, 'SUPPORT_CONVERSATION_STARTED', "Conversation #{$id} started.");

        return response()->json([
            'success' => true,
            'message' => 'Support conversation started.',
            'conversation' => $this->payload($id),
        ], 201);
    }

    public function customerShow(Request $request, int $conversationId): JsonResponse
    {
        $user = $this->customer($request);
        $this->owned($user, $conversationId);

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function customerMessage(Request $request, int $conversationId): JsonResponse
    {
        $user = $this->customer($request);
        $conversation = $this->owned($user, $conversationId);

        if ($conversation->status === 'CLOSED') {
            return response()->json(['success' => false, 'message' => 'Conversation is closed.'], 409);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $text = trim($validated['message']);

        DB::transaction(function () use ($conversation, $user, $text) {
            $this->message((int) $conversation->conversation_id, (int) $user->user_id, 'CUSTOMER', $text);

            if ($conversation->status === 'BOT') {
                $this->message(
                    (int) $conversation->conversation_id,
                    null,
                    'BOT',
                    $this->faqReply($text)
                );
            }
        });

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function customerEscalate(Request $request, int $conversationId): JsonResponse
    {
        $user = $this->customer($request);
        $conversation = $this->owned($user, $conversationId);

        if ($conversation->status === 'CLOSED') {
            return response()->json(['success' => false, 'message' => 'Conversation is closed.'], 409);
        }

        if (!in_array($conversation->status, ['WAITING_STAFF', 'ACTIVE'], true)) {
            DB::transaction(function () use ($conversation, $user) {
                DB::table('WBO_Conversations')
                    ->where('conversation_id', $conversation->conversation_id)
                    ->update([
                        'status' => 'WAITING_STAFF',
                        'assigned_user_id' => null,
                        'updated_at' => now(),
                    ]);

                $this->transfer(
                    (int) $conversation->conversation_id,
                    (int) $user->user_id,
                    null,
                    'ESCALATED',
                    'Customer requested staff assistance.'
                );

                $this->message(
                    (int) $conversation->conversation_id,
                    null,
                    'SYSTEM',
                    'Conversation transferred to Sales Support.'
                );
            });

            $this->notifySales(
                "Support request #{$conversationId}",
                "{$user->name} requested staff assistance."
            );

            $this->audit($request, (int) $user->user_id, 'SUPPORT_ESCALATED', "Conversation #{$conversationId} escalated.");
        }

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function customerClose(Request $request, int $conversationId): JsonResponse
    {
        $user = $this->customer($request);
        $conversation = $this->owned($user, $conversationId);

        $this->closeConversation(
            $conversation,
            (int) $user->user_id,
            'Customer closed the conversation.'
        );

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function staffIndex(Request $request): JsonResponse
    {
        $preview = $this->staffRead($request);
        $this->ready();

        $rows = DB::table('WBO_Conversations as c')
            ->join('WBO_Users as u', 'u.user_id', '=', 'c.customer_user_id')
            ->leftJoin('WBO_Users as a', 'a.user_id', '=', 'c.assigned_user_id')
            ->select('c.*', 'u.name as customer_name', 'u.email as customer_email', 'a.name as assigned_name')
            ->orderByRaw("CASE c.status WHEN 'WAITING_STAFF' THEN 0 WHEN 'ACTIVE' THEN 1 WHEN 'BOT' THEN 2 ELSE 3 END")
            ->orderByDesc('c.updated_at')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $last = DB::table('WBO_ConversationMessages')
                    ->where('conversation_id', $row->conversation_id)
                    ->orderByDesc('message_id')
                    ->first();

                return [
                    'conversation_id' => (int) $row->conversation_id,
                    'customer_name' => $row->customer_name,
                    'customer_email' => $row->customer_email,
                    'assigned_name' => $row->assigned_name,
                    'subject' => $row->subject,
                    'status' => $row->status,
                    'updated_at' => $row->updated_at,
                    'last_message' => $last?->message,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'conversations' => $rows,
        ]);
    }

    public function staffShow(Request $request, int $conversationId): JsonResponse
    {
        $preview = $this->staffRead($request);
        $this->conversation($conversationId);

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function staffClaim(Request $request, int $conversationId): JsonResponse
    {
        $staff = $this->staffAction();
        $conversation = $this->conversation($conversationId);

        if ($conversation->status === 'CLOSED') {
            return response()->json(['success' => false, 'message' => 'Conversation is closed.'], 409);
        }

        if (
            $conversation->assigned_user_id &&
            (int) $conversation->assigned_user_id !== (int) $staff->user_id &&
            $staff->role !== 'Sales_Manager'
        ) {
            return response()->json(['success' => false, 'message' => 'Assigned to another staff member.'], 409);
        }

        DB::transaction(function () use ($conversation, $staff) {
            $old = $conversation->assigned_user_id ? (int) $conversation->assigned_user_id : null;

            DB::table('WBO_Conversations')
                ->where('conversation_id', $conversation->conversation_id)
                ->update([
                    'assigned_user_id' => $staff->user_id,
                    'status' => 'ACTIVE',
                    'updated_at' => now(),
                ]);

            $this->transfer(
                (int) $conversation->conversation_id,
                $old,
                (int) $staff->user_id,
                $old && $old !== (int) $staff->user_id ? 'REASSIGNED' : 'ASSIGNED',
                'Conversation claimed by Sales Support.'
            );

            $this->message(
                (int) $conversation->conversation_id,
                null,
                'SYSTEM',
                "{$staff->name} joined the support conversation."
            );
        });

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function staffMessage(Request $request, int $conversationId): JsonResponse
    {
        $staff = $this->staffAction();
        $conversation = $this->conversation($conversationId);

        if ($conversation->status === 'CLOSED') {
            return response()->json(['success' => false, 'message' => 'Conversation is closed.'], 409);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($conversation, $staff, $validated) {
            if ((int) ($conversation->assigned_user_id ?? 0) !== (int) $staff->user_id) {
                DB::table('WBO_Conversations')
                    ->where('conversation_id', $conversation->conversation_id)
                    ->update([
                        'assigned_user_id' => $staff->user_id,
                        'status' => 'ACTIVE',
                        'updated_at' => now(),
                    ]);

                $this->transfer(
                    (int) $conversation->conversation_id,
                    $conversation->assigned_user_id ? (int) $conversation->assigned_user_id : null,
                    (int) $staff->user_id,
                    $conversation->assigned_user_id ? 'REASSIGNED' : 'ASSIGNED',
                    'Assigned by staff reply.'
                );
            }

            $this->message(
                (int) $conversation->conversation_id,
                (int) $staff->user_id,
                'STAFF',
                trim($validated['message'])
            );
        });

        $this->notifyUser(
            (int) $conversation->customer_user_id,
            'Support replied',
            "New reply in support conversation #{$conversationId}."
        );

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    public function staffClose(Request $request, int $conversationId): JsonResponse
    {
        $staff = $this->staffAction();
        $conversation = $this->conversation($conversationId);

        $this->closeConversation(
            $conversation,
            (int) $staff->user_id,
            'Sales Support closed the conversation.'
        );

        $this->notifyUser(
            (int) $conversation->customer_user_id,
            'Support conversation closed',
            "Support conversation #{$conversationId} was closed."
        );

        return response()->json([
            'success' => true,
            'conversation' => $this->payload($conversationId),
        ]);
    }

    private function customer(Request $request): WBOUser
    {
        if (
            $request->session()->get('logged_in') !== true ||
            $request->session()->get('role') !== 'System_User'
        ) {
            abort(403, 'System User access required.');
        }

        $user = WBOUser::find((int) $request->session()->get('user_id'));

        if (!$user || $user->account_status !== 'active') {
            abort(401, 'Account unavailable.');
        }

        return $user;
    }

    private function staffRead(Request $request): bool
    {
        if (session('logged_in') !== true) {
            abort(401, 'Authentication required.');
        }

        $role = (string) session('role');

        if (in_array($role, self::STAFF_ROLES, true)) {
            return false;
        }

        if ($role === 'super_admin' && $request->boolean('preview')) {
            return true;
        }

        abort(403, 'Sales Support access required.');
    }

    private function staffAction(): WBOUser
    {
        if (
            session('logged_in') !== true ||
            !in_array((string) session('role'), self::STAFF_ROLES, true)
        ) {
            abort(403, 'Actual Sales role required.');
        }

        $user = WBOUser::find((int) session('user_id'));
        abort_unless($user && $user->account_status === 'active', 401);

        return $user;
    }

    private function ready(): void
    {
        if (
            !Schema::hasTable('WBO_Conversations') ||
            !Schema::hasTable('WBO_ConversationMessages') ||
            !Schema::hasTable('WBO_ConversationTransfers')
        ) {
            abort(503, 'Support chat database tables are not installed yet.');
        }
    }

    private function owned(WBOUser $user, int $id): object
    {
        $this->ready();

        $row = DB::table('WBO_Conversations')
            ->where('conversation_id', $id)
            ->where('customer_user_id', $user->user_id)
            ->first();

        abort_unless($row, 404, 'Conversation not found.');
        return $row;
    }

    private function conversation(int $id): object
    {
        $this->ready();
        $row = DB::table('WBO_Conversations')->where('conversation_id', $id)->first();
        abort_unless($row, 404, 'Conversation not found.');
        return $row;
    }

    private function summary(object $row): array
    {
        $last = DB::table('WBO_ConversationMessages')
            ->where('conversation_id', $row->conversation_id)
            ->orderByDesc('message_id')
            ->first();

        return [
            'conversation_id' => (int) $row->conversation_id,
            'subject' => $row->subject,
            'status' => $row->status,
            'updated_at' => $row->updated_at,
            'last_message' => $last?->message,
        ];
    }

    private function payload(int $id): array
    {
        $row = $this->conversation($id);

        $customer = DB::table('WBO_Users')
            ->where('user_id', $row->customer_user_id)
            ->select('user_id', 'name', 'email')
            ->first();

        $assigned = $row->assigned_user_id
            ? DB::table('WBO_Users')
                ->where('user_id', $row->assigned_user_id)
                ->select('user_id', 'name', 'role')
                ->first()
            : null;

        $messages = DB::table('WBO_ConversationMessages as m')
            ->leftJoin('WBO_Users as u', 'u.user_id', '=', 'm.sender_user_id')
            ->where('m.conversation_id', $id)
            ->select('m.*', 'u.name as sender_name')
            ->orderBy('m.message_id')
            ->get()
            ->map(fn ($m) => [
                'message_id' => (int) $m->message_id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'message' => $m->message,
                'created_at' => $m->created_at,
            ])
            ->values();

        return [
            'conversation_id' => (int) $row->conversation_id,
            'subject' => $row->subject,
            'status' => $row->status,
            'customer' => $customer,
            'assigned_staff' => $assigned,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'closed_at' => $row->closed_at,
            'messages' => $messages,
        ];
    }

    private function message(int $conversationId, ?int $userId, string $type, string $text): void
    {
        DB::table('WBO_ConversationMessages')->insert([
            'conversation_id' => $conversationId,
            'sender_user_id' => $userId,
            'sender_type' => $type,
            'message' => $text,
            'created_at' => now(),
        ]);

        DB::table('WBO_Conversations')
            ->where('conversation_id', $conversationId)
            ->update(['updated_at' => now()]);
    }

    private function transfer(
        int $conversationId,
        ?int $from,
        ?int $to,
        string $type,
        string $note
    ): void {
        DB::table('WBO_ConversationTransfers')->insert([
            'conversation_id' => $conversationId,
            'from_user_id' => $from,
            'to_user_id' => $to,
            'transfer_type' => $type,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    private function closeConversation(object $conversation, int $actorId, string $note): void
    {
        if ($conversation->status === 'CLOSED') {
            return;
        }

        DB::transaction(function () use ($conversation, $actorId, $note) {
            DB::table('WBO_Conversations')
                ->where('conversation_id', $conversation->conversation_id)
                ->update([
                    'status' => 'CLOSED',
                    'closed_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->transfer(
                (int) $conversation->conversation_id,
                $actorId,
                $conversation->assigned_user_id ? (int) $conversation->assigned_user_id : null,
                'CLOSED',
                $note
            );

            $this->message(
                (int) $conversation->conversation_id,
                null,
                'SYSTEM',
                $note
            );
        });
    }

    private function faqReply(string $input): string
    {
        $normalized = mb_strtolower(trim($input));

        if (
            str_contains($normalized, 'place an order') ||
            str_contains($normalized, 'order this product')
        ) {
            return 'Open Products, choose an item, add it to your cart, then open Cart and continue to checkout. Review the quantity, delivery information, and available payment option before submitting the order.';
        }

        if (
            str_contains($normalized, 'track my order') ||
            str_contains($normalized, 'status of this order') ||
            str_contains($normalized, 'order status')
        ) {
            return 'Open Orders to view the current status of your submitted orders. You can also use the + button in Support to select a specific order before asking a question.';
        }

        if (str_contains($normalized, 'payment')) {
            return 'Payment information and payment status are linked to your order. Complete checkout using an available payment option. If a submitted order shows an unexpected payment status, choose Talk to staff so Sales Support can review it.';
        }

        if (
            str_contains($normalized, 'delivery information') ||
            str_contains($normalized, 'change my delivery') ||
            str_contains($normalized, 'delivery work')
        ) {
            return 'Your delivery information is entered during checkout and your saved default delivery details can be updated from Account. For an order that has already been submitted, choose Talk to staff before requesting a delivery change.';
        }

        if (
            str_contains($normalized, 'cancel this order') ||
            str_contains($normalized, 'cancel an order')
        ) {
            return 'Cancellation depends on the current order status. Choose Talk to staff so Sales Support can review the selected order before any cancellation is made.';
        }

        if (
            str_contains($normalized, 'leave a review') ||
            str_contains($normalized, 'review for this order')
        ) {
            return 'Fulfilled purchases become eligible for review. Open Reviews and choose the product under Ready to review.';
        }

        if (
            str_contains($normalized, 'damaged') ||
            str_contains($normalized, 'defective')
        ) {
            return 'Please keep the affected order selected and choose Talk to staff. Sales Support can review the order and the damaged or defective item concern with you.';
        }

        if (
            str_contains($normalized, 'currently in stock') ||
            str_contains($normalized, 'product in stock') ||
            str_contains($normalized, 'product available')
        ) {
            return 'The product page shows the current available warehouse quantity. When you select a product in Support, its product card also shows the current stock available.';
        }

        if (
            str_contains($normalized, 'more about this product') ||
            str_contains($normalized, 'product details')
        ) {
            return 'Open the product from Products to view its current price, SKU, available stock, description, ratings, and related products.';
        }

        if (
            str_contains($normalized, 'order these products again') ||
            str_contains($normalized, 'reorder')
        ) {
            return 'Open Products, select the items you want again, and add them to your cart for a new checkout.';
        }

        if (
            str_contains($normalized, 'when will this order be processed') ||
            str_contains($normalized, 'when will this order be fulfilled')
        ) {
            return 'The order status shown in Orders is the current system status. If you need a more specific processing or fulfillment update, keep the order selected and choose Talk to staff.';
        }

        if (
            str_contains($normalized, 'cancelled or unfulfilled') ||
            str_contains($normalized, 'why was this order cancelled')
        ) {
            return 'A cancelled or unfulfilled order may require staff review to explain the exact reason. Keep the order selected and choose Talk to staff.';
        }

        if (!Schema::hasTable('WBO_FAQs')) {
            return 'FAQ is unavailable right now. Choose Talk to staff.';
        }

        $words = collect(preg_split('/[^a-z0-9]+/i', $normalized, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($word) => mb_strlen($word) >= 4)
            ->unique()
            ->values();

        $best = null;
        $score = 0;

        foreach (DB::table('WBO_FAQs')->where('is_active', true)->get() as $faq) {
            $haystack = mb_strtolower($faq->category . ' ' . $faq->question);
            $current = $words->filter(fn ($word) => str_contains($haystack, $word))->count();

            if ($current > $score) {
                $score = $current;
                $best = $faq;
            }
        }

        return $best && $score > 0
            ? (string) $best->answer
            : 'I could not find a close FAQ match. Choose Talk to staff for human assistance.';
    }

    private function notifySales(string $title, string $message): void
    {
        if (!Schema::hasTable('WBO_Notifications')) return;

        $ids = DB::table('WBO_Users')
            ->whereIn('role', self::STAFF_ROLES)
            ->where('account_status', 'active')
            ->pluck('user_id');

        foreach ($ids as $id) {
            $this->notifyUser((int) $id, $title, $message);
        }
    }

    private function notifyUser(int $id, string $title, string $message): void
    {
        if (!Schema::hasTable('WBO_Notifications')) return;

        DB::table('WBO_Notifications')->insert([
            'alert_tier' => 'Yellow',
            'title' => mb_substr($title, 0, 150),
            'message' => mb_substr($message, 0, 500),
            'related_product_id' => null,
            'related_batch_id' => null,
            'recipient_user_id' => $id,
            'triggered_at' => now(),
            'status' => 'UNREAD',
            'acknowledged_at' => null,
            'resolved_at' => null,
        ]);
    }

    private function audit(Request $request, int $userId, string $action, string $description): void
    {
        if (!Schema::hasTable('WBO_AuditLogs')) return;

        DB::table('WBO_AuditLogs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}