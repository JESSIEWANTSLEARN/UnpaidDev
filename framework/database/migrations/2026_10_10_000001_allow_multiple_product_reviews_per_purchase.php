<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_INDEX = 'uq_product_review_user_product';
    private const NEW_INDEX = 'uq_product_review_user_product_order';
    private const USER_FK_INDEX = 'idx_product_review_user';

    public function up(): void
    {
        if (!Schema::hasTable('WBO_ProductReviews')) {
            throw new \RuntimeException('WBO_ProductReviews must exist before changing its review uniqueness rule.');
        }

        // Keep a dedicated user_id index so MySQL can retain the user foreign key
        // while the old composite unique index is replaced.
        if (!$this->hasIndexNamed(self::USER_FK_INDEX)) {
            Schema::table('WBO_ProductReviews', function (Blueprint $table) {
                $table->index(['user_id'], self::USER_FK_INDEX);
            });
        }

        foreach (Schema::getIndexes('WBO_ProductReviews') as $index) {
            if ($this->isUniqueIndexFor($index, ['user_id', 'product_id'])) {
                Schema::table('WBO_ProductReviews', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
        }

        if (!$this->hasUniqueIndexFor(['user_id', 'product_id', 'order_id'])) {
            Schema::table('WBO_ProductReviews', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'product_id', 'order_id'],
                    self::NEW_INDEX
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('WBO_ProductReviews')) {
            throw new \RuntimeException('WBO_ProductReviews must exist before rolling back its review uniqueness rule.');
        }

        foreach (Schema::getIndexes('WBO_ProductReviews') as $index) {
            if ($this->isUniqueIndexFor($index, ['user_id', 'product_id', 'order_id'])) {
                Schema::table('WBO_ProductReviews', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
        }

        if (!$this->hasUniqueIndexFor(['user_id', 'product_id'])) {
            Schema::table('WBO_ProductReviews', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'product_id'],
                    self::OLD_INDEX
                );
            });
        }

        if ($this->hasIndexNamed(self::USER_FK_INDEX)) {
            Schema::table('WBO_ProductReviews', function (Blueprint $table) {
                $table->dropIndex(self::USER_FK_INDEX);
            });
        }
    }

    private function hasIndexNamed(string $name): bool
    {
        foreach (Schema::getIndexes('WBO_ProductReviews') as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    private function hasUniqueIndexFor(array $columns): bool
    {
        foreach (Schema::getIndexes('WBO_ProductReviews') as $index) {
            if ($this->isUniqueIndexFor($index, $columns)) {
                return true;
            }
        }

        return false;
    }

    private function isUniqueIndexFor(array $index, array $columns): bool
    {
        if (empty($index['unique']) || empty($index['name'])) {
            return false;
        }

        $indexedColumns = $index['columns'] ?? [];
        sort($indexedColumns);
        sort($columns);

        return $indexedColumns === $columns;
    }
};
