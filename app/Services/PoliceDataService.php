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
        $categories = collect($crimes)
            ->countBy('category')
            ->sortDesc();

        return [
            'total' => count($crimes),
            'categories' => $categories->all(),
            'top_category' => $categories->keys()->first(),
            'data_month' => $crimes[0]['month'] ?? null,
        ];
    }
}
