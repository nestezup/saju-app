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
            ])->timeout(300)->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 16000,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // 응답 완료 상태 확인
                $finishReason = $data['choices'][0]['finish_reason'] ?? 'unknown';
                Log::info('OpenRouter response', [
                    'finish_reason' => $finishReason,
                    'model' => $data['model'] ?? 'unknown',
                ]);

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
당신은 한국식 명리학(사주팔자)에 정통한 전문가입니다.
아래 사용자 정보를 바탕으로 **원국(사주팔자) → 대운 → 세운 → 종합 조언**을 매우 깊이 있게 해석해 주세요.

필수 요구사항:
1. 네 기둥(년·월·일·시) 천간·지지를 제시하고, 오행 분포를 분석하세요.
2. 신강/신약 판단 + 용신·기신을 도출해 설명하세요.
3. 원국의 인간관계·연애·재물·직업·건강·심리 구조를 해설하세요.
4. 대운을 10년 단위로 세밀하게 정리하고, 각 대운의 성격과 주요 사건 가능성을 제시하세요.
5. 향후 5년간의 **세운(연운)**과 그 의미를 설명하세요.
6. 마지막에 현실에서 어떻게 행동하면 최선인지, "운을 활용하는 전략 가이드라인 10개"를 제시하세요.

전문가 사주풀이처럼 구체적이고 현실적인 해석을 바랍니다.
답변은 한국어로 작성하고, 마크다운 형식으로 구조화해서 보기 좋게 작성해주세요.
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

        $wood = $sajuData['five_elements']['wood'] ?? 0;
        $fire = $sajuData['five_elements']['fire'] ?? 0;
        $earth = $sajuData['five_elements']['earth'] ?? 0;
        $metal = $sajuData['five_elements']['metal'] ?? 0;
        $water = $sajuData['five_elements']['water'] ?? 0;

        $prompt = <<<PROMPT
사주 정보:
- 성별: {$gender}
- 생년월일: {$sajuData['birth_date']} (양력)
- 태어난 시간: {$sajuData['birth_time']}

사주팔자:
- 년주(年柱): {$sajuData['year_pillar']}
- 월주(月柱): {$sajuData['month_pillar']}
- 일주(日柱): {$sajuData['day_pillar']}
- 시주(時柱): {$sajuData['hour_pillar']}

오행 분포 (총 8자):
- 목(木): {$wood}개
- 화(火): {$fire}개
- 토(土): {$earth}개
- 금(金): {$metal}개
- 수(水): {$water}개

위 사주팔자를 분석하여 상세한 운세 해석을 제공해주세요.
PROMPT;

        Log::info('Saju LLM Prompt', ['prompt' => $prompt]);

        return $prompt;
    }
}
