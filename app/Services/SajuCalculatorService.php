<?php

namespace App\Services;

use App\Models\ManseryeokDay;
use App\Models\SolarTerm;
use Carbon\Carbon;

/**
 * 사주 계산 서비스 (DB 만세력 적용)
 */
class SajuCalculatorService
{
    // 천간
    private const STEMS = ['갑', '을', '병', '정', '무', '기', '경', '신', '임', '계'];
    // 지지
    private const BRANCHES = ['자', '축', '인', '묘', '진', '사', '오', '미', '신', '유', '술', '해'];

    // 천간 오행
    private const STEM_ELEMENTS = [
        '갑' => 'wood', '을' => 'wood',
        '병' => 'fire', '정' => 'fire',
        '무' => 'earth', '기' => 'earth',
        '경' => 'metal', '신' => 'metal',
        '임' => 'water', '계' => 'water',
    ];

    // 지지 오행
    private const BRANCH_ELEMENTS = [
        '자' => 'water', '축' => 'earth',
        '인' => 'wood', '묘' => 'wood',
        '진' => 'earth', '사' => 'fire',
        '오' => 'fire', '미' => 'earth',
        '신' => 'metal', '유' => 'metal',
        '술' => 'earth', '해' => 'water',
    ];

    // 시지 매핑
    private const HOUR_BRANCHES = [
        23 => 0, 0 => 0, 1 => 1, 2 => 1, 3 => 2, 4 => 2,
        5 => 3, 6 => 3, 7 => 4, 8 => 4, 9 => 5, 10 => 5,
        11 => 6, 12 => 6, 13 => 7, 14 => 7, 15 => 8, 16 => 8,
        17 => 9, 18 => 9, 19 => 10, 20 => 10, 21 => 11, 22 => 11,
    ];

    // 년간→월간 시작점 (갑기→병, 을경→무, 병신→경, 정임→임, 무계→갑)
    private const YEAR_MONTH_STEM_START = [
        0 => 2, 1 => 4, 2 => 6, 3 => 8, 4 => 0,
        5 => 2, 6 => 4, 7 => 6, 8 => 8, 9 => 0,
    ];

    // 일간→시간 시작점 (갑기→갑, 을경→병, 병신→무, 정임→경, 무계→임)
    private const DAY_HOUR_STEM_START = [
        0 => 0, 1 => 2, 2 => 4, 3 => 6, 4 => 8,
        5 => 0, 6 => 2, 7 => 4, 8 => 6, 9 => 8,
    ];

    /**
     * 사주팔자 계산
     */
    public function calculate(Carbon $birthDate, ?string $birthTime = null): array
    {
        $hour = $this->parseHour($birthTime);

        // 자시(23시) 처리: 다음날 일진 사용
        $dayDate = $birthDate->copy();
        if ($hour === 23) {
            $dayDate->addDay();
        }

        // 절기 기준 년월 계산
        $sajuYear = $this->getSajuYear($birthDate);
        $sajuMonth = $this->getSajuMonth($birthDate);

        // 년주 (입춘 기준)
        $yearPillar = $this->calculateYearPillar($sajuYear);

        // 월주 (절기 기준)
        $monthPillar = $this->calculateMonthPillar($sajuMonth, $yearPillar['stem_index']);

        // 일주 (DB 조회)
        $dayPillar = $this->getDayPillarFromDB($dayDate);

        // 시주 (일간 기준)
        $hourPillar = $this->calculateHourPillar($dayPillar['stem_index'], $hour);

        // 오행 분포
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
                'saju_year' => $sajuYear,
                'saju_month' => $sajuMonth,
            ],
        ];
    }

    /**
     * 사주 년도 (입춘 기준)
     */
    private function getSajuYear(Carbon $date): int
    {
        $year = $date->year;

        // DB에서 입춘 날짜 조회
        $ipchun = SolarTerm::where('year', $year)
            ->where('term_name', '입춘')
            ->first();

        if ($ipchun && $date->lt(Carbon::parse($ipchun->term_date))) {
            return $year - 1;
        }

        return $year;
    }

    /**
     * 사주 월 (절기 기준) - 1=인월, 2=묘월, ... 12=축월
     *
     * 절기→사주월 매핑:
     * 소한(1) → 축월(12), 입춘(3) → 인월(1), 경칩(5) → 묘월(2)
     * 청명(7) → 진월(3), 입하(9) → 사월(4), 망종(11) → 오월(5)
     * 소서(13) → 미월(6), 입추(15) → 신월(7), 백로(17) → 유월(8)
     * 한로(19) → 술월(9), 입동(21) → 해월(10), 대설(23) → 자월(11)
     */
    private function getSajuMonth(Carbon $date): int
    {
        $year = $date->year;

        // 절(節) 기준 월 변경 절기 조회 (is_major = true)
        $terms = SolarTerm::where('year', $year)
            ->where('is_major', true)
            ->orderBy('term_order')
            ->get();

        // 이전 년도의 대설 (자월 시작) 확인 필요
        $prevYearDaeseol = SolarTerm::where('year', $year - 1)
            ->where('term_name', '대설')
            ->first();

        // 해당 년도 소한 이전이면 이전 년도의 자월
        $sohan = $terms->firstWhere('term_name', '소한');
        if ($sohan && $date->lt(Carbon::parse($sohan->term_date))) {
            return 11; // 자월 (이전 년도 대설 이후)
        }

        // 각 절기별로 확인
        $sajuMonth = 12; // 기본값: 축월

        foreach ($terms as $term) {
            $termDate = Carbon::parse($term->term_date);

            if ($date->gte($termDate)) {
                // 해당 절기 이후 → 해당 월
                $sajuMonth = $this->termOrderToSajuMonth($term->term_order);
            } else {
                // 해당 절기 이전 → 이전 월 확정
                break;
            }
        }

        return $sajuMonth;
    }

    /**
     * 절기 순서 → 사주 월 변환
     */
    private function termOrderToSajuMonth(int $termOrder): int
    {
        // order 1(소한)→12, 3(입춘)→1, 5(경칩)→2, ..., 23(대설)→11
        $mapping = [
            1 => 12,  // 소한 → 축월
            3 => 1,   // 입춘 → 인월
            5 => 2,   // 경칩 → 묘월
            7 => 3,   // 청명 → 진월
            9 => 4,   // 입하 → 사월
            11 => 5,  // 망종 → 오월
            13 => 6,  // 소서 → 미월
            15 => 7,  // 입추 → 신월
            17 => 8,  // 백로 → 유월
            19 => 9,  // 한로 → 술월
            21 => 10, // 입동 → 해월
            23 => 11, // 대설 → 자월
        ];

        return $mapping[$termOrder] ?? 12;
    }

    /**
     * 년주 계산
     */
    private function calculateYearPillar(int $year): array
    {
        $stemIndex = (($year - 4) % 10 + 10) % 10;
        $branchIndex = (($year - 4) % 12 + 12) % 12;

        return [
            'stem' => self::STEMS[$stemIndex],
            'branch' => self::BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 월주 계산
     */
    private function calculateMonthPillar(int $month, int $yearStemIndex): array
    {
        // 월지: 인월(1)=인(2), 묘월(2)=묘(3), ...
        $branchIndex = ($month + 1) % 12;

        // 월간
        $monthStemStart = self::YEAR_MONTH_STEM_START[$yearStemIndex];
        $stemIndex = ($monthStemStart + $month - 1) % 10;

        return [
            'stem' => self::STEMS[$stemIndex],
            'branch' => self::BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 일주 DB 조회
     */
    private function getDayPillarFromDB(Carbon $date): array
    {
        $day = ManseryeokDay::find($date->format('Y-m-d'));

        if ($day) {
            return [
                'stem' => $day->stem,
                'branch' => $day->branch,
                'stem_index' => $day->stem_index,
                'branch_index' => $day->branch_index,
            ];
        }

        // DB에 없으면 계산 (fallback)
        return $this->calculateDayPillarFallback($date);
    }

    /**
     * 일주 계산 (fallback)
     * 기준일: 2000-01-01 = 무오일(戊午日)
     */
    private function calculateDayPillarFallback(Carbon $date): array
    {
        $baseDate = Carbon::create(2000, 1, 1);
        $daysDiff = $baseDate->diffInDays($date, false);

        // 무(4), 오(6)
        $stemIndex = ((4 + $daysDiff) % 10 + 10) % 10;
        $branchIndex = ((6 + $daysDiff) % 12 + 12) % 12;

        return [
            'stem' => self::STEMS[$stemIndex],
            'branch' => self::BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 시주 계산
     */
    private function calculateHourPillar(int $dayStemIndex, int $hour): array
    {
        $branchIndex = self::HOUR_BRANCHES[$hour];
        $hourStemStart = self::DAY_HOUR_STEM_START[$dayStemIndex];
        $stemIndex = ($hourStemStart + $branchIndex) % 10;

        return [
            'stem' => self::STEMS[$stemIndex],
            'branch' => self::BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 시간 파싱
     */
    private function parseHour(?string $timeString): int
    {
        if (!$timeString) {
            return 12;
        }

        $koreanTimes = [
            '자시' => 0, '축시' => 2, '인시' => 4, '묘시' => 6,
            '진시' => 8, '사시' => 10, '오시' => 12, '미시' => 14,
            '신시' => 16, '유시' => 18, '술시' => 20, '해시' => 22,
        ];

        if (isset($koreanTimes[$timeString])) {
            return $koreanTimes[$timeString];
        }

        if (preg_match('/^(\d{1,2}):?(\d{2})?$/', $timeString, $matches)) {
            return (int) $matches[1];
        }

        return 12;
    }

    /**
     * 오행 분포 계산
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
            if (isset(self::STEM_ELEMENTS[$pillar['stem']])) {
                $elements[self::STEM_ELEMENTS[$pillar['stem']]]++;
            }
            if (isset(self::BRANCH_ELEMENTS[$pillar['branch']])) {
                $elements[self::BRANCH_ELEMENTS[$pillar['branch']]]++;
            }
        }

        return $elements;
    }
}
