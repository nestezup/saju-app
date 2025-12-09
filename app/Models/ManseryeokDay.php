<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManseryeokDay extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'solar_date';
    protected $keyType = 'date';

    protected $fillable = [
        'solar_date',
        'stem',
        'branch',
        'stem_index',
        'branch_index',
    ];

    protected $casts = [
        'solar_date' => 'date',
        'stem_index' => 'integer',
        'branch_index' => 'integer',
    ];

    /**
     * 일주 (천간 + 지지)
     */
    public function getPillarAttribute(): string
    {
        return $this->stem . $this->branch;
    }
}
