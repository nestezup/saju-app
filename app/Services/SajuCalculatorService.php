<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * 사주 계산 서비스 (만세력 적용)
 *
 * 만세력 기준으로 정확한 사주팔자를 계산합니다.
 * - 년주: 입춘(立春) 기준으로 변경
 * - 월주: 절기(節氣) 기준으로 변경
 * - 일주: 만세력 일진표 기준
 * - 시주: 일간 기준 시간 배치
 */
class SajuCalculatorService
{
    /**
     * 사주팔자 계산 (양력 날짜 기준)
     */
    public function calculate(Carbon $birthDate, ?string $birthTime = null): array
    {
        $hour = $this->parseHour($birthTime);

        // 자시(23:00~01:00) 처리: 23시 이후면 다음날로 계산
        $adjustedDate = $birthDate->copy();
        if ($hour === 23) {
            $adjustedDate->addDay();
        }

        // 절기 기준 년월 계산
        $sajuYear = $this->getSajuYear($birthDate);
        $sajuMonth = $this->getSajuMonth($birthDate);

        // 년주 계산 (입춘 기준)
        $yearPillar = $this->calculateYearPillar($sajuYear);

        // 월주 계산 (절기 기준)
        $monthPillar = $this->calculateMonthPillar($sajuYear, $sajuMonth, $yearPillar['stem_index']);

        // 일주 계산 (만세력 기준)
        $dayPillar = $this->calculateDayPillar($adjustedDate);

        // 시주 계산 (일간 기준)
        $hourPillar = $this->calculateHourPillar($dayPillar['stem_index'], $hour);

        // 오행 분포 계산
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
     * 사주 년도 계산 (입춘 기준)
     * 입춘 이전이면 전년도로 계산
     */
    private function getSajuYear(Carbon $date): int
    {
        $year = $date->year;

        // 해당 년도의 입춘 날짜 가져오기
        $ipchunDate = $this->getSolarTermDate($year, '입춘');

        // 입춘 이전이면 전년도
        if ($date->lt($ipchunDate)) {
            return $year - 1;
        }

        return $year;
    }

    /**
     * 사주 월 계산 (절기 기준)
     * 절입일 기준으로 월이 바뀜 (1=인월, 2=묘월, ...)
     */
    private function getSajuMonth(Carbon $date): int
    {
        $year = $date->year;

        // 각 월의 절입일 확인
        foreach (ManseryeokData::MONTH_TERMS as $month => $term) {
            $termDate = $this->getSolarTermDate($year, $term);

            // 다음 월의 절입일
            $nextMonth = $month === 12 ? 1 : $month + 1;
            $nextTerm = ManseryeokData::MONTH_TERMS[$nextMonth];
            $nextYear = $month === 12 ? $year + 1 : $year;
            $nextTermDate = $this->getSolarTermDate($nextYear, $nextTerm);

            if ($date->gte($termDate) && $date->lt($nextTermDate)) {
                return $month;
            }
        }

        // 소한 이전 (축월)
        return 12;
    }

    /**
     * 절기 날짜 가져오기
     */
    private function getSolarTermDate(int $year, string $term): Carbon
    {
        // 절기 데이터가 있는 경우
        if (isset(ManseryeokData::SOLAR_TERMS[$year][$term])) {
            $monthDay = ManseryeokData::SOLAR_TERMS[$year][$term];
            return Carbon::createFromFormat('Y-m-d', "{$year}-{$monthDay}");
        }

        // 데이터가 없는 경우 근사값 계산
        return $this->calculateApproximateSolarTerm($year, $term);
    }

    /**
     * 절기 근사값 계산 (데이터가 없는 년도용)
     */
    private function calculateApproximateSolarTerm(int $year, string $term): Carbon
    {
        // 24절기 평균 날짜 (근사값)
        $approximateDates = [
            '소한' => '01-06', '대한' => '01-20',
            '입춘' => '02-04', '우수' => '02-19',
            '경칩' => '03-06', '춘분' => '03-21',
            '청명' => '04-05', '곡우' => '04-20',
            '입하' => '05-06', '소만' => '05-21',
            '망종' => '06-06', '하지' => '06-21',
            '소서' => '07-07', '대서' => '07-23',
            '입추' => '08-08', '처서' => '08-23',
            '백로' => '09-08', '추분' => '09-23',
            '한로' => '10-08', '상강' => '10-24',
            '입동' => '11-08', '소설' => '11-22',
            '대설' => '12-07', '동지' => '12-22',
        ];

        $monthDay = $approximateDates[$term] ?? '01-01';
        return Carbon::createFromFormat('Y-m-d', "{$year}-{$monthDay}");
    }

    /**
     * 년주 계산
     */
    private function calculateYearPillar(int $year): array
    {
        // 년간 계산: (년 - 4) % 10
        $stemIndex = (($year - 4) % 10 + 10) % 10;
        // 년지 계산: (년 - 4) % 12
        $branchIndex = (($year - 4) % 12 + 12) % 12;

        return [
            'stem' => ManseryeokData::HEAVENLY_STEMS[$stemIndex],
            'branch' => ManseryeokData::EARTHLY_BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 월주 계산 (절기 기준)
     */
    private function calculateMonthPillar(int $year, int $month, int $yearStemIndex): array
    {
        // 월지: 인월(1)=인(2), 묘월(2)=묘(3), ...
        // month 1~12를 지지 인덱스로 변환
        $branchIndex = ($month + 1) % 12; // 인월=1 -> 인(2)

        // 월간: 년간에 따라 인월 시작 천간이 결정됨
        $monthStemStart = ManseryeokData::YEAR_MONTH_STEM_START[$yearStemIndex];
        $stemIndex = ($monthStemStart + $month - 1) % 10;

        return [
            'stem' => ManseryeokData::HEAVENLY_STEMS[$stemIndex],
            'branch' => ManseryeokData::EARTHLY_BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 일주 계산 (만세력 기준일 사용)
     */
    private function calculateDayPillar(Carbon $date): array
    {
        // 기준일: 2000년 1월 1일 = 갑진일(甲辰日)
        $baseDate = Carbon::create(2000, 1, 1);
        $baseStemIndex = ManseryeokData::DAY_REFERENCE['stem_index'];
        $baseBranchIndex = ManseryeokData::DAY_REFERENCE['branch_index'];

        // 기준일로부터의 일수 차이
        $daysDiff = $baseDate->diffInDays($date, false);

        // 천간 계산 (10일 주기)
        $stemIndex = (($baseStemIndex + $daysDiff) % 10 + 10) % 10;
        // 지지 계산 (12일 주기)
        $branchIndex = (($baseBranchIndex + $daysDiff) % 12 + 12) % 12;

        return [
            'stem' => ManseryeokData::HEAVENLY_STEMS[$stemIndex],
            'branch' => ManseryeokData::EARTHLY_BRANCHES[$branchIndex],
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 시주 계산 (일간 기준)
     */
    private function calculateHourPillar(int $dayStemIndex, int $hour): array
    {
        // 시간을 지지로 변환
        $branch = ManseryeokData::HOUR_BRANCHES[$hour];
        $branchIndex = ManseryeokData::getBranchIndex($branch);

        // 시간: 일간에 따라 자시 시작 천간이 결정됨
        $hourStemStart = ManseryeokData::DAY_HOUR_STEM_START[$dayStemIndex];
        $stemIndex = ($hourStemStart + $branchIndex) % 10;

        return [
            'stem' => ManseryeokData::HEAVENLY_STEMS[$stemIndex],
            'branch' => $branch,
            'stem_index' => $stemIndex,
            'branch_index' => $branchIndex,
        ];
    }

    /**
     * 시간 문자열 파싱
     */
    private function parseHour(?string $timeString): int
    {
        if (!$timeString) {
            return 12; // 기본값: 정오
        }

        // 한국어 시간명 처리
        $koreanTimes = [
            '자시' => 0, '축시' => 2, '인시' => 4, '묘시' => 6,
            '진시' => 8, '사시' => 10, '오시' => 12, '미시' => 14,
            '신시' => 16, '유시' => 18, '술시' => 20, '해시' => 22,
        ];

        if (isset($koreanTimes[$timeString])) {
            return $koreanTimes[$timeString];
        }

        // HH:MM 형식 처리
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
            // 천간 오행
            if (isset(ManseryeokData::STEM_ELEMENTS[$pillar['stem']])) {
                $elements[ManseryeokData::STEM_ELEMENTS[$pillar['stem']]]++;
            }
            // 지지 오행
            if (isset(ManseryeokData::BRANCH_ELEMENTS[$pillar['branch']])) {
                $elements[ManseryeokData::BRANCH_ELEMENTS[$pillar['branch']]]++;
            }
        }

        return $elements;
    }

    /**
     * 오행 한글명 반환
     */
    public function getElementKorean(string $element): string
    {
        $korean = [
            'wood' => '목(木)',
            'fire' => '화(火)',
            'earth' => '토(土)',
            'metal' => '금(金)',
            'water' => '수(水)',
        ];

        return $korean[$element] ?? $element;
    }

    /**
     * 사주 요약 문자열 반환
     */
    public function getSajuSummary(array $saju): string
    {
        return sprintf(
            "년주: %s | 월주: %s | 일주: %s | 시주: %s\n일주(日主): %s",
            $saju['year_pillar'],
            $saju['month_pillar'],
            $saju['day_pillar'],
            $saju['hour_pillar'],
            $saju['day_master']
        );
    }
}
