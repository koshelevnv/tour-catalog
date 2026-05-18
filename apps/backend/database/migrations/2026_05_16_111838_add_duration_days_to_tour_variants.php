<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tour_variants', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_days')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('tour_variants', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
