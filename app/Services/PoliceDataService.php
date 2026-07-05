<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PoliceDataService
{
    public function getCrimeSummary(float $lat, float $lng): array
    {
        $crimes = Cache::remember(
            "crimes:{$lat}:{$lng}",
            now()->addHours(6),
            function () use ($lat, $lng): array {
                $response = Http::get('https://data.police.uk/api/crimes-street/all-crime', [
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

                return $response->successful() ? $response->json() : [];
            }
        );

        return $this->summarise($crimes);
    }

    private function summarise(array $crimes): array
    {
        $crimes = collect($crimes);

        $categories = $crimes->countBy('category')->sortDesc();

        $outcomes = $crimes
            ->map(fn (array $crime): string => $crime['outcome_status']['category'] ?? '__none__')
            ->countBy()
            ->sortDesc();

        return [
            'total' => $crimes->count(),
            'categories' => $categories->all(),
            'top_category' => $categories->keys()->first(),
            'outcomes' => $outcomes->except('__none__')->all(),
            'outcomes_not_recorded' => $outcomes->get('__none__', 0),
            'data_month' => $crimes->first()['month'] ?? null,
        ];
    }
}
