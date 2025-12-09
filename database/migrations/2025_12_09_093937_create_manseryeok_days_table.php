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
        Schema::create('manseryeok_days', function (Blueprint $table) {
            $table->date('solar_date')->primary(); // 양력 날짜 (PK)
            $table->string('stem', 2);             // 천간 (갑,을,병...)
            $table->string('branch', 2);           // 지지 (자,축,인...)
            $table->tinyInteger('stem_index');     // 천간 인덱스 (0-9)
            $table->tinyInteger('branch_index');   // 지지 인덱스 (0-11)
            $table->index(['stem', 'branch']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manseryeok_days');
    }
};
