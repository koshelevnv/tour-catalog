<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('tour_types')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_days');
            $table->timestamps();
        });

        \DB::statement('ALTER TABLE tours ADD COLUMN embedding vector(384)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
