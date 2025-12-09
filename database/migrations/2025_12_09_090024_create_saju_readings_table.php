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
        Schema::create('saju_readings', function (Blueprint $table) {
            $table->id();
            $table->date('birth_date');
            $table->string('birth_date_original');
            $table->string('birth_time')->nullable();
            $table->boolean('is_lunar')->default(false);
            $table->enum('gender', ['male', 'female']);
            $table->text('saju_result')->nullable();
            $table->text('daily_fortune')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saju_readings');
    }
};
