<?php

namespace App\Livewire;

use App\Services\LunarCalendarService;
use App\Services\OpenRouterService;
use App\Services\SajuCalculatorService;
use Carbon\Carbon;
use Livewire\Component;

class SajuForm extends Component
{
    public string $birthDate = '';
    public string $birthTime = '';
    public bool $isLunar = false;
    public string $gender = 'male';

    public bool $isLoading = false;
    public bool $isAnalyzing = false;
    public ?array $result = null;
    public ?string $analysis = null;
    public ?string $error = null;

    protected $rules = [
        'birthDate' => 'required|date_format:Y-m-d',
        'birthTime' => 'nullable|string',
        'isLunar' => 'boolean',
        'gender' => 'required|in:male,female',
    ];

    protected $messages = [
        'birthDate.required' => '생년월일을 입력해주세요.',
        'birthDate.date_format' => '올바른 날짜 형식을 입력해주세요. (YYYY-MM-DD)',
        'gender.required' => '성별을 선택해주세요.',
    ];

    public function submit()
    {
        $this->validate();

        $this->isLoading = true;
        $this->error = null;
        $this->result = null;
        $this->analysis = null;

        try {
            // Parse birth date
            $lunarService = new LunarCalendarService();
            $dateInfo = $lunarService->parseBirthDate($this->birthDate, $this->isLunar);
            $solarDate = $dateInfo['solar_date'];

            // Calculate Saju
            $sajuCalculator = new SajuCalculatorService();
            $sajuData = $sajuCalculator->calculate($solarDate, $this->birthTime ?: null);

            // 결과 저장
            $this->result = [
                'birth_date' => $solarDate->format('Y년 m월 d일'),
                'birth_time' => $this->birthTime ?: '미입력',
                'is_lunar' => $this->isLunar,
                'gender' => $this->gender,
                'year_pillar' => $sajuData['year_pillar'],
                'month_pillar' => $sajuData['month_pillar'],
                'day_pillar' => $sajuData['day_pillar'],
                'hour_pillar' => $sajuData['hour_pillar'],
                'five_elements' => $sajuData['five_elements'],
                'metadata' => $sajuData['metadata'],
            ];

            $this->isLoading = false;

            // LLM 분석 요청
            $this->analyzeSaju($sajuData, $solarDate);

        } catch (\Exception $e) {
            $this->error = '사주 분석 중 오류가 발생했습니다: ' . $e->getMessage();
            $this->isLoading = false;
        }
    }

    public function analyzeSaju(array $sajuData, Carbon $solarDate)
    {
        $this->isAnalyzing = true;

        try {
            $openRouter = new OpenRouterService();

            // LLM에 전달할 데이터 구성
            $llmData = [
                'gender' => $this->gender,
                'birth_date' => $solarDate->format('Y년 m월 d일'),
                'birth_time' => $this->birthTime ?: '미입력',
                'year_pillar' => $sajuData['year_pillar'],
                'month_pillar' => $sajuData['month_pillar'],
                'day_pillar' => $sajuData['day_pillar'],
                'hour_pillar' => $sajuData['hour_pillar'],
                'five_elements' => $sajuData['five_elements'],
            ];

            $this->analysis = $openRouter->generateSajuReading($llmData);

        } catch (\Exception $e) {
            $this->analysis = '분석을 불러오는 중 오류가 발생했습니다.';
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function resetForm()
    {
        $this->reset(['birthDate', 'birthTime', 'isLunar', 'gender', 'result', 'analysis', 'error', 'isAnalyzing']);
    }

    public function render()
    {
        return view('livewire.saju-form');
    }
}
