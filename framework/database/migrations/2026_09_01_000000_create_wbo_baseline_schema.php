<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Register/create the pre-Phase-3 WalangBrownout schema.
     *
     * Every statement uses CREATE TABLE IF NOT EXISTS, so this migration is
     * safe to register against an existing WalangBrownout database while also
     * allowing a new developer to build the business schema from source.
     */
    public function up(): void
    {
        $path = database_path('schema/walangbrownout_baseline.sql');

        if (!is_file($path)) {
            throw new RuntimeException(
                'Missing WalangBrownout baseline schema: '.$path
            );
        }

        $sql = (string) file_get_contents($path);

        // The committed schema contains only CREATE TABLE statements.
        // Split on statement terminators and execute in dependency order.
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if ($statement === '' || str_starts_with($statement, '--')) {
                // Strip leading comment lines before deciding whether to run it.
                $statement = preg_replace('/^(?:\s*--[^\n]*\n)+/', '', $statement) ?? '';
                $statement = trim($statement);
            }

            if ($statement !== '') {
                DB::unprepared($statement);
            }
        }
    }

    /**
     * The baseline represents long-lived business tables and may be registered
     * against databases that already contain real data. Rollback is therefore
     * intentionally non-destructive.
     */
    public function down(): void
    {
        // Intentionally left blank. Never drop the business baseline on rollback.
    }
};
