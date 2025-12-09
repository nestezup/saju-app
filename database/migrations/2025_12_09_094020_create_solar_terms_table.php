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
        Schema::create('solar_terms', function (Blueprint $table) {
            $table->id();
            $table->year('year');                      // 년도
            $table->string('term_name', 10);           // 절기 이름 (입춘, 경칩...)
            $table->date('term_date');                 // 절기 날짜
            $table->time('term_time')->nullable();     // 절기 시각 (정확한 시간)
            $table->tinyInteger('term_order');         // 절기 순서 (1-24)
            $table->boolean('is_major')->default(false); // 절(節) 여부 (월 변경 기준)
            $table->unique(['year', 'term_name']);
            $table->index(['year', 'term_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_terms');
    }
};
