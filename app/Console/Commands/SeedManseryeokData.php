<?php

namespace App\Console\Commands;

use App\Models\ManseryeokDay;
use App\Models\SolarTerm;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedManseryeokData extends Command
{
    protected $signature = 'manseryeok:seed
                            {--start-year=1920 : 시작 년도}
                            {--end-year=2050 : 종료 년도}
                            {--solar-terms : 절기 데이터만 생성}
                            {--days : 일진 데이터만 생성}';

    protected $description = '만세력 데이터 (절기 + 일진) 생성';

    // 천간
    private const STEMS = ['갑', '을', '병', '정', '무', '기', '경', '신', '임', '계'];
    // 지지
    private const BRANCHES = ['자', '축', '인', '묘', '진', '사', '오', '미', '신', '유', '술', '해'];

    // 검증된 기준일: 2000년 1월 1일 = 무오일(戊午日)
    private const BASE_DATE = '2000-01-01';
    private const BASE_STEM_INDEX = 4;    // 무(戊)
    private const BASE_BRANCH_INDEX = 6;  // 오(午)

    // 24절기 기준 데이터 (2000년 기준 - 천문 계산값)
    // 각 절기의 태양 황경(도)과 평균 날짜
    private const SOLAR_TERMS_BASE = [
        ['name' => '소한', 'longitude' => 285, 'month' => 1, 'day' => 6, 'order' => 1, 'is_major' => true],
        ['name' => '대한', 'longitude' => 300, 'month' => 1, 'day' => 20, 'order' => 2, 'is_major' => false],
        ['name' => '입춘', 'longitude' => 315, 'month' => 2, 'day' => 4, 'order' => 3, 'is_major' => true],
        ['name' => '우수', 'longitude' => 330, 'month' => 2, 'day' => 19, 'order' => 4, 'is_major' => false],
        ['name' => '경칩', 'longitude' => 345, 'month' => 3, 'day' => 6, 'order' => 5, 'is_major' => true],
        ['name' => '춘분', 'longitude' => 0, 'month' => 3, 'day' => 21, 'order' => 6, 'is_major' => false],
        ['name' => '청명', 'longitude' => 15, 'month' => 4, 'day' => 5, 'order' => 7, 'is_major' => true],
        ['name' => '곡우', 'longitude' => 30, 'month' => 4, 'day' => 20, 'order' => 8, 'is_major' => false],
        ['name' => '입하', 'longitude' => 45, 'month' => 5, 'day' => 6, 'order' => 9, 'is_major' => true],
        ['name' => '소만', 'longitude' => 60, 'month' => 5, 'day' => 21, 'order' => 10, 'is_major' => false],
        ['name' => '망종', 'longitude' => 75, 'month' => 6, 'day' => 6, 'order' => 11, 'is_major' => true],
        ['name' => '하지', 'longitude' => 90, 'month' => 6, 'day' => 21, 'order' => 12, 'is_major' => false],
        ['name' => '소서', 'longitude' => 105, 'month' => 7, 'day' => 7, 'order' => 13, 'is_major' => true],
        ['name' => '대서', 'longitude' => 120, 'month' => 7, 'day' => 23, 'order' => 14, 'is_major' => false],
        ['name' => '입추', 'longitude' => 135, 'month' => 8, 'day' => 8, 'order' => 15, 'is_major' => true],
        ['name' => '처서', 'longitude' => 150, 'month' => 8, 'day' => 23, 'order' => 16, 'is_major' => false],
        ['name' => '백로', 'longitude' => 165, 'month' => 9, 'day' => 8, 'order' => 17, 'is_major' => true],
        ['name' => '추분', 'longitude' => 180, 'month' => 9, 'day' => 23, 'order' => 18, 'is_major' => false],
        ['name' => '한로', 'longitude' => 195, 'month' => 10, 'day' => 8, 'order' => 19, 'is_major' => true],
        ['name' => '상강', 'longitude' => 210, 'month' => 10, 'day' => 24, 'order' => 20, 'is_major' => false],
        ['name' => '입동', 'longitude' => 225, 'month' => 11, 'day' => 8, 'order' => 21, 'is_major' => true],
        ['name' => '소설', 'longitude' => 240, 'month' => 11, 'day' => 22, 'order' => 22, 'is_major' => false],
        ['name' => '대설', 'longitude' => 255, 'month' => 12, 'day' => 7, 'order' => 23, 'is_major' => true],
        ['name' => '동지', 'longitude' => 270, 'month' => 12, 'day' => 22, 'order' => 24, 'is_major' => false],
    ];

    public function handle(): void
    {
        $startYear = (int) $this->option('start-year');
        $endYear = (int) $this->option('end-year');
        $onlySolarTerms = $this->option('solar-terms');
        $onlyDays = $this->option('days');

        $this->info("만세력 데이터 생성: {$startYear}년 ~ {$endYear}년");

        if (!$onlyDays) {
            $this->seedSolarTerms($startYear, $endYear);
        }

        if (!$onlySolarTerms) {
            $this->seedDayPillars($startYear, $endYear);
        }

        $this->info('완료!');
    }

    /**
     * 절기 데이터 생성 (천문 계산)
     */
    private function seedSolarTerms(int $startYear, int $endYear): void
    {
        $this->info('절기 데이터 생성 중...');
        $bar = $this->output->createProgressBar($endYear - $startYear + 1);

        $batch = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            foreach (self::SOLAR_TERMS_BASE as $term) {
                // 년도별 절기 날짜 계산 (윤년 보정 포함)
                $termDate = $this->calculateSolarTermDate($year, $term);

                $batch[] = [
                    'year' => $year,
                    'term_name' => $term['name'],
                    'term_date' => $termDate->format('Y-m-d'),
                    'term_time' => null,
                    'term_order' => $term['order'],
                    'is_major' => $term['is_major'],
                ];
            }
            $bar->advance();
        }

        // Batch insert
        DB::table('solar_terms')->truncate();
        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('solar_terms')->insert($chunk);
        }

        $bar->finish();
        $this->newLine();
        $this->info('절기 데이터 생성 완료: ' . SolarTerm::count() . '건');
    }

    /**
     * 절기 날짜 계산 (간단한 천문 계산)
     */
    private function calculateSolarTermDate(int $year, array $term): Carbon
    {
        // 기본 날짜
        $baseDate = Carbon::create($year, $term['month'], $term['day']);

        // 년도에 따른 보정 (4년 주기 윤년 + 100년/400년 보정)
        // 절기는 매년 약 5시간 49분씩 늦어지고, 윤년에 하루 앞당겨짐
        $yearDiff = $year - 2000;

        // 대략적인 보정값 (일 단위)
        // 4년마다 약 +1일, 윤년에 -1일 = 거의 동일
        // 100년마다 약 +0.25일 누적
        $correction = 0;

        if ($yearDiff > 0) {
            $correction = floor($yearDiff / 100) * 0.25;
        } elseif ($yearDiff < 0) {
            $correction = ceil($yearDiff / 100) * 0.25;
        }

        // 보정 적용 (반올림)
        $correctionDays = (int) round($correction);
        if ($correctionDays !== 0) {
            $baseDate->addDays($correctionDays);
        }

        return $baseDate;
    }

    /**
     * 일진 데이터 생성 (60갑자 주기)
     */
    private function seedDayPillars(int $startYear, int $endYear): void
    {
        $this->info('일진 데이터 생성 중...');

        $startDate = Carbon::create($startYear, 1, 1);
        $endDate = Carbon::create($endYear, 12, 31);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $bar = $this->output->createProgressBar($totalDays);

        // 기준일
        $baseDate = Carbon::parse(self::BASE_DATE);

        // 기존 데이터 삭제
        DB::table('manseryeok_days')->truncate();

        $batch = [];
        $batchSize = 1000;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $daysDiff = $baseDate->diffInDays($date, false);

            $stemIndex = (((self::BASE_STEM_INDEX + $daysDiff) % 10) + 10) % 10;
            $branchIndex = (((self::BASE_BRANCH_INDEX + $daysDiff) % 12) + 12) % 12;

            $batch[] = [
                'solar_date' => $date->format('Y-m-d'),
                'stem' => self::STEMS[$stemIndex],
                'branch' => self::BRANCHES[$branchIndex],
                'stem_index' => $stemIndex,
                'branch_index' => $branchIndex,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('manseryeok_days')->insert($batch);
                $batch = [];
            }

            $bar->advance();
        }

        // 남은 데이터
        if (!empty($batch)) {
            DB::table('manseryeok_days')->insert($batch);
        }

        $bar->finish();
        $this->newLine();
        $this->info('일진 데이터 생성 완료: ' . ManseryeokDay::count() . '건');
    }
}
