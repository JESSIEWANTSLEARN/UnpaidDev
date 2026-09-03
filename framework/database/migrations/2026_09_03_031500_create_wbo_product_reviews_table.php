<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('WBO_ProductReviews')) return;

        Schema::create('WBO_ProductReviews', function (Blueprint $table) {
            $table->increments('review_id');
            $table->integer('product_id');
            $table->integer('user_id');
            $table->integer('order_id');
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('comment');
            $table->boolean('verified_purchase')->default(true);
            $table->enum('status', ['VISIBLE','HIDDEN','FLAGGED'])->default('VISIBLE');
            $table->string('moderation_reason', 255)->nullable();
            $table->integer('moderated_by_user_id')->nullable();
            $table->dateTime('moderated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id','product_id'], 'uq_product_review_user_product');
            $table->index(['product_id','status'], 'idx_product_review_product_status');
            $table->index(['rating','status'], 'idx_product_review_rating_status');
            $table->foreign('product_id')->references('product_id')->on('WBO_Products')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('WBO_Users')->cascadeOnDelete();
            $table->foreign('order_id')->references('order_id')->on('WBO_Orders')->cascadeOnDelete();
            $table->foreign('moderated_by_user_id')->references('user_id')->on('WBO_Users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('WBO_ProductReviews');
    }
};