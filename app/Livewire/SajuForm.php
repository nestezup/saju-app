<?php

namespace App\Livewire;

use App\Models\SajuReading;
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
    public ?SajuReading $result = null;
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

        try {
            // Parse birth date
            $lunarService = new LunarCalendarService();
            $dateInfo = $lunarService->parseBirthDate($this->birthDate, $this->isLunar);
            $solarDate = $dateInfo['solar_date'];

            // Calculate Saju
            $sajuCalculator = new SajuCalculatorService();
            $sajuData = $sajuCalculator->calculate($solarDate, $this->birthTime ?: null);
            $sajuData['gender'] = $this->gender;
            $sajuData['birth_date'] = $solarDate->format('Y년 m월 d일');
            $sajuData['birth_time'] = $this->birthTime ?: '미입력';

            // Generate reading using OpenRouter
            $openRouter = new OpenRouterService();
            $sajuResult = $openRouter->generateSajuReading($sajuData);
            $dailyFortune = $openRouter->generateDailyFortune($sajuData);

            // Save to database
            $reading = SajuReading::create([
                'birth_date' => $solarDate,
                'birth_date_original' => $this->birthDate,
                'birth_time' => $this->birthTime ?: null,
                'is_lunar' => $this->isLunar,
                'gender' => $this->gender,
                'saju_result' => $sajuResult,
                'daily_fortune' => $dailyFortune,
                'metadata' => $sajuData['metadata'],
            ]);

            $this->result = $reading;
        } catch (\Exception $e) {
            $this->error = '사주 분석 중 오류가 발생했습니다: ' . $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    public function resetForm()
    {
        $this->reset(['birthDate', 'birthTime', 'isLunar', 'gender', 'result', 'error']);
    }

    public function render()
    {
        return view('livewire.saju-form');
    }
}
