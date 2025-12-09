<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LunarCalendarService
{
    /**
     * Convert lunar date to solar date
     */
    public function lunarToSolar(int $year, int $month, int $day, bool $isLeapMonth = false): ?Carbon
    {
        // Use Korean Astronomical Data API or fallback to calculation
        try {
            $response = Http::get('https://astro.kasi.re.kr/life/lsolp', [
                'ly' => $year,
                'lm' => $month,
                'ld' => $day,
                'leap' => $isLeapMonth ? '1' : '0',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['sy'], $data['sm'], $data['sd'])) {
                    return Carbon::create($data['sy'], $data['sm'], $data['sd']);
                }
            }
        } catch (\Exception $e) {
            // Fallback to approximate calculation
        }

        // Fallback: Return approximate date (lunar is roughly 1 month behind solar)
        return $this->approximateLunarToSolar($year, $month, $day);
    }

    /**
     * Approximate lunar to solar conversion
     * Note: This is not accurate but serves as a fallback
     */
    private function approximateLunarToSolar(int $year, int $month, int $day): Carbon
    {
        // Lunar calendar is approximately 10-11 days behind solar
        // This is a very rough approximation
        $baseDate = Carbon::create($year, $month, $day);

        // Add approximately 30-33 days to convert from lunar to solar
        // This is not accurate but serves as emergency fallback
        return $baseDate->addDays(30);
    }

    /**
     * Parse birth date string and return solar date
     */
    public function parseBirthDate(string $dateString, bool $isLunar): array
    {
        // Parse date string (format: YYYY-MM-DD or YYYY/MM/DD)
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
}
