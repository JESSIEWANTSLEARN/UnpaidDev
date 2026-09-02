<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('WBO_PasswordHistory')) {
            return;
        }

        Schema::create('WBO_PasswordHistory', function (Blueprint $table) {
            $table->bigIncrements('password_history_id');

            // WBO_Users.user_id is an INT in the existing WalangBrownout schema.
            $table->integer('user_id');

            // Only password hashes are stored. Plain-text passwords are never retained.
            $table->string('password_hash', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['user_id', 'created_at'],
                'idx_wbo_password_history_user_created'
            );

            $table->foreign(
                'user_id',
                'fk_wbo_password_history_user'
            )
                ->references('user_id')
                ->on('WBO_Users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('WBO_PasswordHistory');
    }
};