<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsiteContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WebsiteContentController extends Controller
{
    public function publicIndex(
        WebsiteContentService $content
    ): JsonResponse {
        return response()->json(
            $content->publicPayload()
        );
    }

    public function teamPhoto(
        int $teamMemberId,
        WebsiteContentService $content
    ): Response {
        $content->ensureReady();

        $row = DB::table('WBO_TeamMembers')
            ->where('team_member_id', $teamMemberId)
            ->select(
                'photo_data',
                'mime_type',
                'updated_at'
            )
            ->first();

        if (!$row || !$row->photo_data || !$row->mime_type) {
            abort(404);
        }

        return response(
            $row->photo_data,
            200,
            [
                'Content-Type' => $row->mime_type,
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function adminIndex(
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();

        return response()->json(
            $content->adminPayload()
        );
    }

    public function updateAbout(
        Request $request,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:4000'],
            'visible' => ['required', 'boolean'],
        ]);

        $content->setAbout(
            trim($validated['title']),
            trim($validated['description']),
            (bool) $validated['visible'],
            (int) session('user_id')
        );

        $this->audit(
            $request,
            'WEBSITE_ABOUT_UPDATED',
            'Updated the public About section.'
        );

        return response()->json([
            'message' => 'About section updated.',
            'data' => $content->adminPayload(),
        ]);
    }

    public function createFaq(
        Request $request,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        $validated = $this->validateFaq($request);

        $faqId = DB::table('WBO_FAQs')->insertGetId([
            'category' => trim($validated['category']),
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) $validated['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit(
            $request,
            'FAQ_CREATED',
            "Created FAQ #{$faqId}."
        );

        return response()->json([
            'message' => 'FAQ created.',
            'data' => $content->adminPayload(),
        ], 201);
    }

    public function updateFaq(
        Request $request,
        int $faqId,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        $validated = $this->validateFaq($request);

        $updated = DB::table('WBO_FAQs')
            ->where('faq_id', $faqId)
            ->update([
                'category' => trim($validated['category']),
                'question' => trim($validated['question']),
                'answer' => trim($validated['answer']),
                'sort_order' => (int) $validated['sort_order'],
                'is_active' => (bool) $validated['is_active'],
                'updated_at' => now(),
            ]);

        if (
            $updated === 0 &&
            !DB::table('WBO_FAQs')
                ->where('faq_id', $faqId)
                ->exists()
        ) {
            return response()->json([
                'message' => 'FAQ not found.',
            ], 404);
        }

        $this->audit(
            $request,
            'FAQ_UPDATED',
            "Updated FAQ #{$faqId}."
        );

        return response()->json([
            'message' => 'FAQ updated.',
            'data' => $content->adminPayload(),
        ]);
    }

    public function deleteFaq(
        Request $request,
        int $faqId,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        $deleted = DB::table('WBO_FAQs')
            ->where('faq_id', $faqId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'FAQ not found.',
            ], 404);
        }

        $this->audit(
            $request,
            'FAQ_DELETED',
            "Deleted FAQ #{$faqId}."
        );

        return response()->json([
            'message' => 'FAQ deleted.',
            'data' => $content->adminPayload(),
        ]);
    }

    public function createTeamMember(
        Request $request,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        $validated = $this->validateTeam($request);

        $photo = $this->photoValues($request);

        $teamMemberId = DB::table('WBO_TeamMembers')
            ->insertGetId([
                'name' => trim($validated['name']),
                'role' => trim($validated['role']),
                'description' => trim((string) ($validated['description'] ?? '')),
                'photo_data' => $photo['photo_data'],
                'mime_type' => $photo['mime_type'],
                'file_size' => $photo['file_size'],
                'sort_order' => (int) $validated['sort_order'],
                'is_visible' => (bool) $validated['is_visible'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->audit(
            $request,
            'TEAM_MEMBER_CREATED',
            "Created website team member #{$teamMemberId}."
        );

        return response()->json([
            'message' => 'Team member created.',
            'data' => $content->adminPayload(),
        ], 201);
    }

    public function updateTeamMember(
        Request $request,
        int $teamMemberId,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        if (
            !DB::table('WBO_TeamMembers')
                ->where('team_member_id', $teamMemberId)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Team member not found.',
            ], 404);
        }

        $validated = $this->validateTeam($request);

        $values = [
            'name' => trim($validated['name']),
            'role' => trim($validated['role']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'sort_order' => (int) $validated['sort_order'],
            'is_visible' => (bool) $validated['is_visible'],
            'updated_at' => now(),
        ];

        if ($request->hasFile('photo')) {
            $values = array_merge(
                $values,
                $this->photoValues($request)
            );
        }

        DB::table('WBO_TeamMembers')
            ->where('team_member_id', $teamMemberId)
            ->update($values);

        $this->audit(
            $request,
            'TEAM_MEMBER_UPDATED',
            "Updated website team member #{$teamMemberId}."
        );

        return response()->json([
            'message' => 'Team member updated.',
            'data' => $content->adminPayload(),
        ]);
    }

    public function deleteTeamPhoto(
        Request $request,
        int $teamMemberId,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        $updated = DB::table('WBO_TeamMembers')
            ->where('team_member_id', $teamMemberId)
            ->update([
                'photo_data' => null,
                'mime_type' => null,
                'file_size' => null,
                'updated_at' => now(),
            ]);

        if (
            $updated === 0 &&
            !DB::table('WBO_TeamMembers')
                ->where('team_member_id', $teamMemberId)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Team member not found.',
            ], 404);
        }

        $this->audit(
            $request,
            'TEAM_MEMBER_PHOTO_REMOVED',
            "Removed website team member #{$teamMemberId} photo."
        );

        return response()->json([
            'message' => 'Team photo removed.',
            'data' => $content->adminPayload(),
        ]);
    }

    public function deleteTeamMember(
        Request $request,
        int $teamMemberId,
        WebsiteContentService $content
    ): JsonResponse {
        $this->authorizeSuperAdmin();
        $content->ensureReady();

        $deleted = DB::table('WBO_TeamMembers')
            ->where('team_member_id', $teamMemberId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Team member not found.',
            ], 404);
        }

        $this->audit(
            $request,
            'TEAM_MEMBER_DELETED',
            "Deleted website team member #{$teamMemberId}."
        );

        return response()->json([
            'message' => 'Team member deleted.',
            'data' => $content->adminPayload(),
        ]);
    }

    private function validateFaq(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'between:-10000,10000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function validateTeam(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['required', 'integer', 'between:-10000,10000'],
            'is_visible' => ['required'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]);
    }

    private function photoValues(Request $request): array
    {
        if (!$request->hasFile('photo')) {
            return [
                'photo_data' => null,
                'mime_type' => null,
                'file_size' => null,
            ];
        }

        $file = $request->file('photo');

        return [
            'photo_data' => file_get_contents(
                $file->getRealPath()
            ),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    private function authorizeSuperAdmin(): void
    {
        if (
            session('logged_in') !== true ||
            !session('user_id')
        ) {
            abort(401, 'Unauthenticated.');
        }

        if (session('role') !== 'super_admin') {
            abort(403, 'Forbidden.');
        }
    }

    private function audit(
        Request $request,
        string $action,
        string $description
    ): void {
        try {
            DB::table('WBO_AuditLogs')->insert([
                'user_id' => (int) session('user_id'),
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr(
                    (string) $request->userAgent(),
                    0,
                    500
                ),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}