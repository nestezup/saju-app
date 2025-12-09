<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key');
        $this->model = config('services.openrouter.model', 'z-ai/glm-4.5-air:free');
    }

    /**
     * Send a chat completion request
     */
    public function chat(string $prompt, ?string $systemPrompt = null): ?string
    {
        $messages = [];

        if ($systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])->timeout(120)->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenRouter API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OpenRouter API exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate Saju (Four Pillars) interpretation
     */
    public function generateSajuReading(array $sajuData): ?string
    {
        $systemPrompt = <<<PROMPT
당신은 전문 사주명리학자입니다. 사용자의 사주팔자를 분석하여 운세를 해석해주세요.
분석은 다음 항목을 포함해야 합니다:
1. 사주 구성 설명 (년주, 월주, 일주, 시주)
2. 오행 분석
3. 전체적인 성격과 운명
4. 적성과 직업운
5. 대인관계와 인연
6. 건강운
7. 조언

답변은 한국어로 작성하고, 친절하고 이해하기 쉽게 설명해주세요.
PROMPT;

        $prompt = $this->buildSajuPrompt($sajuData);

        return $this->chat($prompt, $systemPrompt);
    }

    /**
     * Generate daily fortune
     */
    public function generateDailyFortune(array $sajuData): ?string
    {
        $systemPrompt = <<<PROMPT
당신은 전문 사주명리학자입니다. 사용자의 사주를 기반으로 오늘의 운세를 작성해주세요.
운세는 다음 항목을 포함해야 합니다:
1. 오늘의 총운 (한 줄 요약)
2. 재물운
3. 애정운
4. 건강운
5. 오늘의 행운 색상과 숫자
6. 오늘 주의할 점

답변은 한국어로 작성하고, 긍정적이고 희망적인 톤으로 작성해주세요.
PROMPT;

        $today = now()->format('Y년 m월 d일');
        $prompt = $this->buildSajuPrompt($sajuData) . "\n\n오늘 날짜: {$today}\n위 사주를 바탕으로 오늘의 운세를 알려주세요.";

        return $this->chat($prompt, $systemPrompt);
    }

    /**
     * Build prompt from saju data
     */
    private function buildSajuPrompt(array $sajuData): string
    {
        $gender = $sajuData['gender'] === 'male' ? '남성' : '여성';

        return <<<PROMPT
사주 정보:
- 성별: {$gender}
- 생년월일: {$sajuData['birth_date']} (양력)
- 태어난 시간: {$sajuData['birth_time']}

사주팔자:
- 년주(年柱): {$sajuData['year_pillar']}
- 월주(月柱): {$sajuData['month_pillar']}
- 일주(日柱): {$sajuData['day_pillar']}
- 시주(時柱): {$sajuData['hour_pillar']}

오행 분포:
- 목(木): {$sajuData['five_elements']['wood']}
- 화(火): {$sajuData['five_elements']['fire']}
- 토(土): {$sajuData['five_elements']['earth']}
- 금(金): {$sajuData['five_elements']['metal']}
- 수(水): {$sajuData['five_elements']['water']}

위 사주팔자를 분석하여 상세한 운세 해석을 제공해주세요.
PROMPT;
    }
}
