<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SajuReading extends Model
{
    /** @use HasFactory<\Database\Factories\SajuReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'birth_date',
        'birth_date_original',
        'birth_time',
        'is_lunar',
        'gender',
        'saju_result',
        'daily_fortune',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_lunar' => 'boolean',
        'metadata' => 'array',
    ];
}
