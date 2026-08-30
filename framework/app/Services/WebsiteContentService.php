<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsiteContentService
{
    public function ensureReady(): void
    {
        $this->ensureTables();
        $this->seedDefaults();
    }

    public function publicPayload(): array
    {
        $this->ensureReady();

        return [
            'about' => $this->about(),
            'faqs' => $this->faqRows(true),
            'team' => $this->teamRows(true),
        ];
    }

    public function adminPayload(): array
    {
        $this->ensureReady();

        return [
            'about' => $this->about(),
            'faqs' => $this->faqRows(false),
            'team' => $this->teamRows(false),
        ];
    }

    public function about(): array
    {
        return [
            'title' => (string) (
                DB::table('WBO_SystemSettings')
                    ->where(
                        'setting_key',
                        'website_about_title'
                    )
                    ->value('setting_value')
                ?: 'About Walang BrownOut'
            ),
            'description' => (string) (
                DB::table('WBO_SystemSettings')
                    ->where(
                        'setting_key',
                        'website_about_description'
                    )
                    ->value('setting_value')
                ?: ''
            ),
            'visible' => (
                (string) DB::table('WBO_SystemSettings')
                    ->where(
                        'setting_key',
                        'website_about_visible'
                    )
                    ->value('setting_value')
            ) !== '0',
        ];
    }

    public function setAbout(
        string $title,
        string $description,
        bool $visible,
        ?int $userId
    ): void {
        $this->ensureReady();

        $this->setContent(
            'website_about_title',
            $title,
            $userId
        );
        $this->setContent(
            'website_about_description',
            $description,
            $userId
        );
        $this->setContent(
            'website_about_visible',
            $visible ? '1' : '0',
            $userId
        );
    }

    public function faqRows(bool $publicOnly): array
    {
        $query = DB::table('WBO_FAQs')
            ->select(
                'faq_id',
                'category',
                'question',
                'answer',
                'sort_order',
                'is_active',
                'created_at',
                'updated_at'
            );

        if ($publicOnly) {
            $query->where('is_active', true);
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('faq_id')
            ->get()
            ->map(function ($faq) {
                $faq->faq_id = (int) $faq->faq_id;
                $faq->sort_order = (int) $faq->sort_order;
                $faq->is_active = (bool) $faq->is_active;

                return $faq;
            })
            ->values()
            ->all();
    }

    public function teamRows(bool $publicOnly): array
    {
        $query = DB::table('WBO_TeamMembers')
            ->select(
                'team_member_id',
                'name',
                'role',
                'description',
                'mime_type',
                'file_size',
                'sort_order',
                'is_visible',
                'created_at',
                'updated_at',
                DB::raw(
                    'CASE WHEN photo_data IS NULL THEN 0 ELSE 1 END AS has_photo'
                )
            );

        if ($publicOnly) {
            $query->where('is_visible', true);
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('team_member_id')
            ->get()
            ->map(function ($member) {
                $member->team_member_id = (int) $member->team_member_id;
                $member->file_size = $member->file_size !== null
                    ? (int) $member->file_size
                    : null;
                $member->sort_order = (int) $member->sort_order;
                $member->is_visible = (bool) $member->is_visible;
                $member->has_photo = (bool) $member->has_photo;
                $member->photo_url = $member->has_photo
                    ? '/api/public/team/' . $member->team_member_id . '/photo?v='
                        . urlencode((string) ($member->updated_at ?? '1'))
                    : null;

                return $member;
            })
            ->values()
            ->all();
    }

    private function ensureTables(): void
    {
        if (!Schema::hasTable('WBO_FAQs')) {
            Schema::create('WBO_FAQs', function (Blueprint $table) {
                $table->increments('faq_id');
                $table->string('category', 100)->default('General');
                $table->string('question', 500);
                $table->text('answer');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('WBO_TeamMembers')) {
            Schema::create('WBO_TeamMembers', function (Blueprint $table) {
                $table->increments('team_member_id');
                $table->string('name', 150);
                $table->string('role', 150);
                $table->text('description')->nullable();

                // Laravel Blueprint has no mediumBlob() method.
                // Create the column first, then promote it to MySQL MEDIUMBLOB.
                $table->binary('photo_data')->nullable();

                $table->string('mime_type', 50)->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });

            DB::statement(
                'ALTER TABLE WBO_TeamMembers MODIFY photo_data MEDIUMBLOB NULL'
            );
        }
    }

    private function seedDefaults(): void
    {
        $defaults = [
            'website_about_title' => [
                'value' => 'About Walang BrownOut',
                'type' => 'STRING',
                'description' =>
                    'Public website About section title.',
            ],
            'website_about_description' => [
                'value' =>
                    'Walang BrownOut Appliances is a regional distributor of home comfort products dedicated to improving everyday living through dependable cooling, clean air, and smarter energy use. We serve households, offices, and retail partners with efficient solutions built for comfort, health, and performance.',
                'type' => 'STRING',
                'description' =>
                    'Public website About section description.',
            ],
            'website_about_visible' => [
                'value' => '1',
                'type' => 'BOOLEAN',
                'description' =>
                    'Controls whether the public About section is visible.',
            ],
        ];

        foreach ($defaults as $key => $setting) {
            if (
                !DB::table('WBO_SystemSettings')
                    ->where('setting_key', $key)
                    ->exists()
            ) {
                DB::table('WBO_SystemSettings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $setting['value'],
                    'setting_type' => $setting['type'],
                    'is_sensitive' => false,
                    'description' =>
                        $setting['description'],
                    'updated_by_user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('WBO_FAQs')->count() === 0) {
            $faqs = [
                [
                    'category' => 'General',
                    'question' => 'What is the Walang BrownOut system?',
                    'answer' => 'Walang BrownOut is an online ordering and inventory management system for portable air conditioners, air purifiers, replacement filters, smart thermostats, and related warehouse operations.',
                    'sort_order' => 10,
                ],
                [
                    'category' => 'Inventory',
                    'question' => 'Are product quantities updated automatically?',
                    'answer' => 'Inventory changes are reflected from recorded stock movements such as received stock, sales, releases, returns, and approved adjustments, helping the system keep available quantities current.',
                    'sort_order' => 20,
                ],
                [
                    'category' => 'Inventory',
                    'question' => 'What happens when a product reaches low stock?',
                    'answer' => 'The system creates low-stock or out-of-stock alerts when inventory reaches the warning level. Authorized inventory and purchasing roles can review the item and prepare the next action.',
                    'sort_order' => 30,
                ],
                [
                    'category' => 'Purchasing',
                    'question' => 'Does the system automatically order from suppliers?',
                    'answer' => 'No. The system helps staff identify stock concerns, but an authorized purchasing employee must still review and manage purchase orders.',
                    'sort_order' => 40,
                ],
                [
                    'category' => 'Inventory',
                    'question' => 'How does the system help manage expiring filters?',
                    'answer' => 'Replacement filters can be recorded by batch with expiration dates. The notification system can warn authorized staff about batches approaching expiration so near-expiry inventory can be reviewed first.',
                    'sort_order' => 50,
                ],
                [
                    'category' => 'Orders',
                    'question' => 'Can customers place and track orders?',
                    'answer' => 'Yes. Registered customers can browse available products, add items to their cart, complete checkout, receive order notifications, and view current or previous orders from their account.',
                    'sort_order' => 60,
                ],
                [
                    'category' => 'Inventory',
                    'question' => 'How are missing inventory items detected?',
                    'answer' => 'Stock movements are recorded in the inventory transaction history. Staff can compare recorded quantities with physical stock and investigate or document adjustments when a difference is discovered.',
                    'sort_order' => 70,
                ],
                [
                    'category' => 'Access',
                    'question' => 'Who can access purchasing functions?',
                    'answer' => 'Purchasing-related functions are limited by role. Authorized roles include the Purchasing Manager, Purchasing Staff, Operations Manager, and Super Admin according to assigned permissions.',
                    'sort_order' => 80,
                ],
                [
                    'category' => 'Project',
                    'question' => 'Who developed the Walang BrownOut system?',
                    'answer' => 'The system was designed and developed by the Walang BrownOut Development Team as an academic project focused on improving inventory, purchasing, warehouse, sales, and customer-order management.',
                    'sort_order' => 90,
                ],
                [
                    'category' => 'Project',
                    'question' => 'Who contributed to the creation of the website?',
                    'answer' => 'The project was completed through the combined work of the project manager, developers, front-end contributor, Scrum Master, and quality analysis contributor. The team and assigned roles are listed below.',
                    'sort_order' => 100,
                ],
            ];

            foreach ($faqs as $faq) {
                DB::table('WBO_FAQs')->insert(array_merge($faq, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if (DB::table('WBO_TeamMembers')->count() === 0) {
            $members = [
                [
                    'name' => 'Jerome Raymundo',
                    'role' => 'Project Manager',
                    'description' => 'Coordinates the project scope, priorities, team responsibilities, and overall delivery.',
                    'sort_order' => 10,
                ],
                [
                    'name' => 'John Jessie Palarao',
                    'role' => 'Lead Developer',
                    'description' => 'Leads the core development, integration, database implementation, and overall system functionality.',
                    'sort_order' => 20,
                ],
                [
                    'name' => 'Jhon Paul Villasanta',
                    'role' => 'Front End',
                    'description' => 'Contributes to front-end implementation, page structure, and user-interface development.',
                    'sort_order' => 30,
                ],
                [
                    'name' => 'Taironne James Sieteriales',
                    'role' => 'Scrum Master',
                    'description' => 'Supports the Scrum workflow, coordination, progress tracking, and removal of team blockers.',
                    'sort_order' => 40,
                ],
                [
                    'name' => 'John Lorena',
                    'role' => 'Quality Analysis',
                    'description' => 'Reviews system quality, tests expected behavior, and helps identify issues before delivery.',
                    'sort_order' => 50,
                ],
            ];

            foreach ($members as $member) {
                DB::table('WBO_TeamMembers')->insert(array_merge($member, [
                    'photo_data' => null,
                    'mime_type' => null,
                    'file_size' => null,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function setContent(
        string $key,
        ?string $value,
        ?int $userId
    ): void {
        $isVisible =
            $key === 'website_about_visible';

        $description = match ($key) {
            'website_about_title' =>
                'Public website About section title.',
            'website_about_description' =>
                'Public website About section description.',
            'website_about_visible' =>
                'Controls whether the public About section is visible.',
            default =>
                'Public website setting.',
        };

        DB::table('WBO_SystemSettings')->updateOrInsert(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_type' =>
                    $isVisible ? 'BOOLEAN' : 'STRING',
                'is_sensitive' => false,
                'description' => $description,
                'updated_by_user_id' => $userId,
                'updated_at' => now(),
            ]
        );
    }
}