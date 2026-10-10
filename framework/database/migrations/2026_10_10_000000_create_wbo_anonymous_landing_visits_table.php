<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('WBO_AnonymousLandingVisits', function (Blueprint $table) {
            $table->bigIncrements('visit_id');
            $table->char('visitor_hash', 64);
            $table->date('visit_date');
            $table->dateTime('first_seen_at');
            $table->unique(
                ['visitor_hash', 'visit_date'],
                'wbo_anon_landing_visitor_day_unique'
            );
            $table->index('visit_date', 'wbo_anon_landing_visit_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('WBO_AnonymousLandingVisits');
    }
};
