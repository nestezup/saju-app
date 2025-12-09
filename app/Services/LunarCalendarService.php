<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * 음력-양력 변환 서비스
 * 만세력 기반의 음양력 변환 제공
 */
class LunarCalendarService
{
    /**
     * 음력 데이터 (1900-2100년)
     * 각 년도별 음력 정보 (16진수 형태)
     * 상위 4비트: 윤달 위치 (0이면 윤달 없음)
     * 하위 12비트: 각 월의 대소 (1=대월30일, 0=소월29일)
     */
    private const LUNAR_INFO = [
        0x04bd8, 0x04ae0, 0x0a570, 0x054d5, 0x0d260, 0x0d950, 0x16554, 0x056a0, 0x09ad0, 0x055d2, // 1900-1909
        0x04ae0, 0x0a5b6, 0x0a4d0, 0x0d250, 0x1d255, 0x0b540, 0x0d6a0, 0x0ada2, 0x095b0, 0x14977, // 1910-1919
        0x04970, 0x0a4b0, 0x0b4b5, 0x06a50, 0x06d40, 0x1ab54, 0x02b60, 0x09570, 0x052f2, 0x04970, // 1920-1929
        0x06566, 0x0d4a0, 0x0ea50, 0x06e95, 0x05ad0, 0x02b60, 0x186e3, 0x092e0, 0x1c8d7, 0x0c950, // 1930-1939
        0x0d4a0, 0x1d8a6, 0x0b550, 0x056a0, 0x1a5b4, 0x025d0, 0x092d0, 0x0d2b2, 0x0a950, 0x0b557, // 1940-1949
        0x06ca0, 0x0b550, 0x15355, 0x04da0, 0x0a5b0, 0x14573, 0x052b0, 0x0a9a8, 0x0e950, 0x06aa0, // 1950-1959
        0x0aea6, 0x0ab50, 0x04b60, 0x0aae4, 0x0a570, 0x05260, 0x0f263, 0x0d950, 0x05b57, 0x056a0, // 1960-1969
        0x096d0, 0x04dd5, 0x04ad0, 0x0a4d0, 0x0d4d4, 0x0d250, 0x0d558, 0x0b540, 0x0b6a0, 0x195a6, // 1970-1979
        0x095b0, 0x049b0, 0x0a974, 0x0a4b0, 0x0b27a, 0x06a50, 0x06d40, 0x0af46, 0x0ab60, 0x09570, // 1980-1989
        0x04af5, 0x04970, 0x064b0, 0x074a3, 0x0ea50, 0x06b58, 0x055c0, 0x0ab60, 0x096d5, 0x092e0, // 1990-1999
        0x0c960, 0x0d954, 0x0d4a0, 0x0da50, 0x07552, 0x056a0, 0x0abb7, 0x025d0, 0x092d0, 0x0cab5, // 2000-2009
        0x0a950, 0x0b4a0, 0x0baa4, 0x0ad50, 0x055d9, 0x04ba0, 0x0a5b0, 0x15176, 0x052b0, 0x0a930, // 2010-2019
        0x07954, 0x06aa0, 0x0ad50, 0x05b52, 0x04b60, 0x0a6e6, 0x0a4e0, 0x0d260, 0x0ea65, 0x0d530, // 2020-2029
        0x05aa0, 0x076a3, 0x096d0, 0x04afb, 0x04ad0, 0x0a4d0, 0x1d0b6, 0x0d250, 0x0d520, 0x0dd45, // 2030-2039
        0x0b5a0, 0x056d0, 0x055b2, 0x049b0, 0x0a577, 0x0a4b0, 0x0aa50, 0x1b255, 0x06d20, 0x0ada0, // 2040-2049
        0x14b63, 0x09370, 0x049f8, 0x04970, 0x064b0, 0x168a6, 0x0ea50, 0x06b20, 0x1a6c4, 0x0aae0, // 2050-2059
        0x0a2e0, 0x0d2e3, 0x0c960, 0x0d557, 0x0d4a0, 0x0da50, 0x05d55, 0x056a0, 0x0a6d0, 0x055d4, // 2060-2069
        0x052d0, 0x0a9b8, 0x0a950, 0x0b4a0, 0x0b6a6, 0x0ad50, 0x055a0, 0x0aba4, 0x0a5b0, 0x052b0, // 2070-2079
        0x0b273, 0x06930, 0x07337, 0x06aa0, 0x0ad50, 0x14b55, 0x04b60, 0x0a570, 0x054e4, 0x0d160, // 2080-2089
        0x0e968, 0x0d520, 0x0daa0, 0x16aa6, 0x056d0, 0x04ae0, 0x0a9d4, 0x0a4d0, 0x0d150, 0x0f252, // 2090-2099
        0x0d520, // 2100
    ];

    /**
     * 음력 기준일 (양력 1900년 1월 31일 = 음력 1900년 1월 1일)
     */
    private const LUNAR_BASE_DATE = '1900-01-31';

    /**
     * 음력을 양력으로 변환
     */
    public function lunarToSolar(int $lunarYear, int $lunarMonth, int $lunarDay, bool $isLeapMonth = false): ?Carbon
    {
        if ($lunarYear < 1900 || $lunarYear > 2100) {
            return $this->approximateLunarToSolar($lunarYear, $lunarMonth, $lunarDay);
        }

        // 1900년 1월 1일(음력)부터의 총 일수 계산
        $offset = 0;

        // 년도별 일수 계산
        for ($y = 1900; $y < $lunarYear; $y++) {
            $offset += $this->getLunarYearDays($y);
        }

        // 월별 일수 계산
        $leapMonth = $this->getLeapMonth($lunarYear);
        for ($m = 1; $m < $lunarMonth; $m++) {
            // 윤달인지 확인
            if ($leapMonth > 0 && $m === $leapMonth) {
                $offset += $this->getLeapMonthDays($lunarYear);
            }
            $offset += $this->getLunarMonthDays($lunarYear, $m);
        }

        // 윤달 처리
        if ($isLeapMonth && $lunarMonth === $leapMonth) {
            $offset += $this->getLunarMonthDays($lunarYear, $lunarMonth);
        }

        // 일수 추가
        $offset += $lunarDay - 1;

        // 기준일에서 offset만큼 더하기
        return Carbon::parse(self::LUNAR_BASE_DATE)->addDays($offset);
    }

    /**
     * 양력을 음력으로 변환
     */
    public function solarToLunar(int $solarYear, int $solarMonth, int $solarDay): array
    {
        $solarDate = Carbon::create($solarYear, $solarMonth, $solarDay);
        $baseDate = Carbon::parse(self::LUNAR_BASE_DATE);

        if ($solarDate->lt($baseDate)) {
            return $this->approximateSolarToLunar($solarYear, $solarMonth, $solarDay);
        }

        // 기준일로부터의 일수 차이
        $offset = $baseDate->diffInDays($solarDate);

        // 년도 찾기
        $lunarYear = 1900;
        while ($lunarYear < 2100) {
            $yearDays = $this->getLunarYearDays($lunarYear);
            if ($offset < $yearDays) {
                break;
            }
            $offset -= $yearDays;
            $lunarYear++;
        }

        // 월 찾기
        $leapMonth = $this->getLeapMonth($lunarYear);
        $isLeapMonth = false;
        $lunarMonth = 1;

        while ($lunarMonth <= 12) {
            $monthDays = $this->getLunarMonthDays($lunarYear, $lunarMonth);

            if ($offset < $monthDays) {
                break;
            }
            $offset -= $monthDays;

            // 윤달 확인
            if ($leapMonth > 0 && $lunarMonth === $leapMonth) {
                $leapDays = $this->getLeapMonthDays($lunarYear);
                if ($offset < $leapDays) {
                    $isLeapMonth = true;
                    break;
                }
                $offset -= $leapDays;
            }

            $lunarMonth++;
        }

        $lunarDay = $offset + 1;

        return [
            'year' => $lunarYear,
            'month' => $lunarMonth,
            'day' => $lunarDay,
            'is_leap_month' => $isLeapMonth,
        ];
    }

    /**
     * 특정 음력 년도의 총 일수
     */
    private function getLunarYearDays(int $year): int
    {
        $sum = 348; // 기본 12개월 * 29일
        $info = self::LUNAR_INFO[$year - 1900];

        // 각 월의 대월 추가
        for ($i = 0x8000; $i > 0x8; $i >>= 1) {
            $sum += ($info & $i) ? 1 : 0;
        }

        // 윤달 추가
        $sum += $this->getLeapMonthDays($year);

        return $sum;
    }

    /**
     * 특정 음력 년도의 윤달
     */
    private function getLeapMonth(int $year): int
    {
        if ($year < 1900 || $year > 2100) {
            return 0;
        }
        return self::LUNAR_INFO[$year - 1900] & 0xf;
    }

    /**
     * 특정 음력 년도의 윤달 일수
     */
    private function getLeapMonthDays(int $year): int
    {
        if ($this->getLeapMonth($year) === 0) {
            return 0;
        }
        return (self::LUNAR_INFO[$year - 1900] & 0x10000) ? 30 : 29;
    }

    /**
     * 특정 음력 월의 일수
     */
    private function getLunarMonthDays(int $year, int $month): int
    {
        if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
            return 30;
        }
        return (self::LUNAR_INFO[$year - 1900] & (0x10000 >> $month)) ? 30 : 29;
    }

    /**
     * 근사값 음력->양력 변환 (범위 외 년도용)
     */
    private function approximateLunarToSolar(int $year, int $month, int $day): Carbon
    {
        // 음력은 양력보다 대략 30~33일 뒤
        $baseDate = Carbon::create($year, $month, $day);
        return $baseDate->addDays(30);
    }

    /**
     * 근사값 양력->음력 변환 (범위 외 년도용)
     */
    private function approximateSolarToLunar(int $year, int $month, int $day): array
    {
        // 양력에서 대략 30일 빼기
        $date = Carbon::create($year, $month, $day)->subDays(30);
        return [
            'year' => $date->year,
            'month' => $date->month,
            'day' => $date->day,
            'is_leap_month' => false,
        ];
    }

    /**
     * 생년월일 문자열 파싱
     */
    public function parseBirthDate(string $dateString, bool $isLunar): array
    {
        $dateString = str_replace('/', '-', $dateString);
        $parts = explode('-', $dateString);

        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid date format. Use YYYY-MM-DD');
        }

        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];

        if ($isLunar) {
            $solarDate = $this->lunarToSolar($year, $month, $day);
        } else {
            $solarDate = Carbon::create($year, $month, $day);
        }

        return [
            'original' => $dateString,
            'solar_date' => $solarDate,
            'is_lunar' => $isLunar,
        ];
    }

    /**
     * 특정 날짜의 음력 정보 문자열
     */
    public function getLunarDateString(Carbon $solarDate): string
    {
        $lunar = $this->solarToLunar($solarDate->year, $solarDate->month, $solarDate->day);
        $leapStr = $lunar['is_leap_month'] ? '(윤)' : '';
        return sprintf('%d년 %s%d월 %d일', $lunar['year'], $leapStr, $lunar['month'], $lunar['day']);
    }
}
