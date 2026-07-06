<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PoliceDataService
{
    public function getCrimeSummary(float $lat, float $lng): array
    {
        $key = "crimes:{$lat}:{$lng}";

        $crimes = Cache::get($key);

        if ($crimes === null) {
            $crimes = $this->fetchCrimes($lat, $lng);

            if ($crimes !== null) {
                Cache::put($key, $crimes, config('services.police.cache_ttl'));
            }
        }

        return $this->summarise($crimes ?? []);
    }

    private function fetchCrimes(float $lat, float $lng): ?array
    {
        try {
            $response = Http::baseUrl(config('services.police.url'))
                ->timeout(15)
                ->retry(2, 300, throw: false)
                ->get('/crimes-street/all-crime', ['lat' => $lat, 'lng' => $lng]);
        } catch (ConnectionException $e) {
            Log::warning('Police data service unreachable', ['lat' => $lat, 'lng' => $lng, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Police data lookup failed', ['lat' => $lat, 'lng' => $lng, 'status' => $response->status()]);

            return null;
        }

        return $response->json();
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
