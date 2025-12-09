<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolarTerm extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'year',
        'term_name',
        'term_date',
        'term_time',
        'term_order',
        'is_major',
    ];

    protected $casts = [
        'year' => 'integer',
        'term_date' => 'date',
        'term_order' => 'integer',
        'is_major' => 'boolean',
    ];

    /**
     * 절기 순서 및 이름 매핑
     * is_major = true: 절(節) - 월 변경 기준
     * is_major = false: 기(氣)
     */
    public const TERMS = [
        1 => ['name' => '소한', 'is_major' => true, 'month' => 12],   // 축월 시작
        2 => ['name' => '대한', 'is_major' => false, 'month' => 12],
        3 => ['name' => '입춘', 'is_major' => true, 'month' => 1],    // 인월 시작 (새해)
        4 => ['name' => '우수', 'is_major' => false, 'month' => 1],
        5 => ['name' => '경칩', 'is_major' => true, 'month' => 2],    // 묘월 시작
        6 => ['name' => '춘분', 'is_major' => false, 'month' => 2],
        7 => ['name' => '청명', 'is_major' => true, 'month' => 3],    // 진월 시작
        8 => ['name' => '곡우', 'is_major' => false, 'month' => 3],
        9 => ['name' => '입하', 'is_major' => true, 'month' => 4],    // 사월 시작
        10 => ['name' => '소만', 'is_major' => false, 'month' => 4],
        11 => ['name' => '망종', 'is_major' => true, 'month' => 5],   // 오월 시작
        12 => ['name' => '하지', 'is_major' => false, 'month' => 5],
        13 => ['name' => '소서', 'is_major' => true, 'month' => 6],   // 미월 시작
        14 => ['name' => '대서', 'is_major' => false, 'month' => 6],
        15 => ['name' => '입추', 'is_major' => true, 'month' => 7],   // 신월 시작
        16 => ['name' => '처서', 'is_major' => false, 'month' => 7],
        17 => ['name' => '백로', 'is_major' => true, 'month' => 8],   // 유월 시작
        18 => ['name' => '추분', 'is_major' => false, 'month' => 8],
        19 => ['name' => '한로', 'is_major' => true, 'month' => 9],   // 술월 시작
        20 => ['name' => '상강', 'is_major' => false, 'month' => 9],
        21 => ['name' => '입동', 'is_major' => true, 'month' => 10],  // 해월 시작
        22 => ['name' => '소설', 'is_major' => false, 'month' => 10],
        23 => ['name' => '대설', 'is_major' => true, 'month' => 11],  // 자월 시작
        24 => ['name' => '동지', 'is_major' => false, 'month' => 11],
    ];

    /**
     * 절기 이름으로 순서 찾기
     */
    public static function getTermOrder(string $name): ?int
    {
        foreach (self::TERMS as $order => $term) {
            if ($term['name'] === $name) {
                return $order;
            }
        }
        return null;
    }

    /**
     * 절(節)인지 확인 (월 변경 기준)
     */
    public static function isMajorTerm(string $name): bool
    {
        $order = self::getTermOrder($name);
        return $order ? self::TERMS[$order]['is_major'] : false;
    }
}
