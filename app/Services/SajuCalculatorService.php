<?php

namespace App\Services;

use Carbon\Carbon;

class SajuCalculatorService
{
    // 천간 (Heavenly Stems) - 10개
    private const HEAVENLY_STEMS = ['갑', '을', '병', '정', '무', '기', '경', '신', '임', '계'];
    private const HEAVENLY_STEMS_HANJA = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    // 지지 (Earthly Branches) - 12개
    private const EARTHLY_BRANCHES = ['자', '축', '인', '묘', '진', '사', '오', '미', '신', '유', '술', '해'];
    private const EARTHLY_BRANCHES_HANJA = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    // 오행 (Five Elements)
    private const FIVE_ELEMENTS = [
        '갑' => 'wood', '을' => 'wood',
        '병' => 'fire', '정' => 'fire',
        '무' => 'earth', '기' => 'earth',
        '경' => 'metal', '신' => 'metal',
        '임' => 'water', '계' => 'water',
        '자' => 'water', '축' => 'earth',
        '인' => 'wood', '묘' => 'wood',
        '진' => 'earth', '사' => 'fire',
        '오' => 'fire', '미' => 'earth',
        '신' => 'metal', '유' => 'metal',
        '술' => 'earth', '해' => 'water',
    ];

    // 시간대별 지지
    private const HOUR_BRANCHES = [
        0 => '자', 1 => '자',    // 23:00 - 01:00
        2 => '축', 3 => '축',    // 01:00 - 03:00
        4 => '인', 5 => '인',    // 03:00 - 05:00
        6 => '묘', 7 => '묘',    // 05:00 - 07:00
        8 => '진', 9 => '진',    // 07:00 - 09:00
        10 => '사', 11 => '사',  // 09:00 - 11:00
        12 => '오', 13 => '오',  // 11:00 - 13:00
        14 => '미', 15 => '미',  // 13:00 - 15:00
        16 => '신', 17 => '신',  // 15:00 - 17:00
        18 => '유', 19 => '유',  // 17:00 - 19:00
        20 => '술', 21 => '술',  // 19:00 - 21:00
        22 => '해', 23 => '해',  // 21:00 - 23:00
    ];

    /**
     * Calculate Saju (Four Pillars) from birth date and time
     */
    public function calculate(Carbon $birthDate, ?string $birthTime = null): array
    {
        $year = $birthDate->year;
        $month = $birthDate->month;
        $day = $birthDate->day;
        $hour = $this->parseHour($birthTime);

        // Calculate each pillar
        $yearPillar = $this->calculateYearPillar($year);
        $monthPillar = $this->calculateMonthPillar($year, $month);
        $dayPillar = $this->calculateDayPillar($birthDate);
        $hourPillar = $this->calculateHourPillar($dayPillar['stem'], $hour);

        // Calculate five elements distribution
        $fiveElements = $this->calculateFiveElements([
            $yearPillar, $monthPillar, $dayPillar, $hourPillar
        ]);

        return [
            'year_pillar' => $yearPillar['stem'] . $yearPillar['branch'],
            'month_pillar' => $monthPillar['stem'] . $monthPillar['branch'],
            'day_pillar' => $dayPillar['stem'] . $dayPillar['branch'],
            'hour_pillar' => $hourPillar['stem'] . $hourPillar['branch'],
            'five_elements' => $fiveElements,
            'day_master' => $dayPillar['stem'],
            'metadata' => [
                'year_gan' => $yearPillar['stem'],
                'year_ji' => $yearPillar['branch'],
                'month_gan' => $monthPillar['stem'],
                'month_ji' => $monthPillar['branch'],
                'day_gan' => $dayPillar['stem'],
                'day_ji' => $dayPillar['branch'],
                'hour_gan' => $hourPillar['stem'],
                'hour_ji' => $hourPillar['branch'],
                'five_elements' => $fiveElements,
                'day_master' => $dayPillar['stem'],
            ],
        ];
    }

    /**
     * Parse hour from time string
     */
    private function parseHour(?string $timeString): int
    {
        if (!$timeString) {
            return 12; // Default to noon if not provided
        }

        // Handle Korean time names
        $koreanTimes = [
            '자시' => 0, '축시' => 2, '인시' => 4, '묘시' => 6,
            '진시' => 8, '사시' => 10, '오시' => 12, '미시' => 14,
            '신시' => 16, '유시' => 18, '술시' => 20, '해시' => 22,
        ];

        if (isset($koreanTimes[$timeString])) {
            return $koreanTimes[$timeString];
        }

        // Handle HH:MM format
        if (preg_match('/^(\d{1,2}):?(\d{2})?$/', $timeString, $matches)) {
            return (int) $matches[1];
        }

        return 12;
    }

    /**
     * Calculate year pillar
     */
    private function calculateYearPillar(int $year): array
    {
        // 년간 계산: (년 - 4) % 10
        $stemIndex = ($year - 4) % 10;
        // 년지 계산: (년 - 4) % 12
        $branchIndex = ($year - 4) % 12;

        return [
            'stem' => self::HEAVENLY_STEMS[$stemIndex],
            'branch' => self::EARTHLY_BRANCHES[$branchIndex],
        ];
    }

    /**
     * Calculate month pillar
     */
    private function calculateMonthPillar(int $year, int $month): array
    {
        // 월지는 고정 (입춘 기준이지만 간단히 처리)
        // 1월=축, 2월=인, 3월=묘, ... 12월=자
        $monthBranches = [
            1 => '축', 2 => '인', 3 => '묘', 4 => '진',
            5 => '사', 6 => '오', 7 => '미', 8 => '신',
            9 => '유', 10 => '술', 11 => '해', 12 => '자',
        ];

        $branch = $monthBranches[$month];
        $branchIndex = array_search($branch, self::EARTHLY_BRANCHES);

        // 월간 계산: 년간에 따라 결정
        $yearStemIndex = ($year - 4) % 10;
        // 월간 공식: (년간 * 2 + 월) % 10
        $stemIndex = (($yearStemIndex % 5) * 2 + $month) % 10;

        return [
            'stem' => self::HEAVENLY_STEMS[$stemIndex],
            'branch' => $branch,
        ];
    }

    /**
     * Calculate day pillar using a base date method
     */
    private function calculateDayPillar(Carbon $date): array
    {
        // Use January 1, 1900 as base date (갑자일)
        $baseDate = Carbon::create(1900, 1, 1);
        $daysDiff = $baseDate->diffInDays($date, false);

        // 1900년 1월 1일은 갑자일 (index 0)
        // Add offset for the actual 1900-01-01 day pillar
        $baseDayOffset = 0; // 갑자

        $totalDays = $daysDiff + $baseDayOffset;

        // Ensure positive index
        $stemIndex = (($totalDays % 10) + 10) % 10;
        $branchIndex = (($totalDays % 12) + 12) % 12;

        return [
            'stem' => self::HEAVENLY_STEMS[$stemIndex],
            'branch' => self::EARTHLY_BRANCHES[$branchIndex],
        ];
    }

    /**
     * Calculate hour pillar
     */
    private function calculateHourPillar(string $dayStem, int $hour): array
    {
        // Adjust for 자시 starting at 23:00
        if ($hour === 23) {
            $hour = 0;
        }

        $branch = self::HOUR_BRANCHES[$hour];
        $branchIndex = array_search($branch, self::EARTHLY_BRANCHES);

        // 시간 계산: 일간에 따라 시간이 결정됨
        $dayStemIndex = array_search($dayStem, self::HEAVENLY_STEMS);

        // 시간 공식
        $hourStemBase = ($dayStemIndex % 5) * 2;
        $stemIndex = ($hourStemBase + $branchIndex) % 10;

        return [
            'stem' => self::HEAVENLY_STEMS[$stemIndex],
            'branch' => $branch,
        ];
    }

    /**
     * Calculate five elements distribution
     */
    private function calculateFiveElements(array $pillars): array
    {
        $elements = [
            'wood' => 0,
            'fire' => 0,
            'earth' => 0,
            'metal' => 0,
            'water' => 0,
        ];

        foreach ($pillars as $pillar) {
            // Count stem element
            if (isset(self::FIVE_ELEMENTS[$pillar['stem']])) {
                $elements[self::FIVE_ELEMENTS[$pillar['stem']]]++;
            }
            // Count branch element
            if (isset(self::FIVE_ELEMENTS[$pillar['branch']])) {
                $elements[self::FIVE_ELEMENTS[$pillar['branch']]]++;
            }
        }

        return $elements;
    }
}
