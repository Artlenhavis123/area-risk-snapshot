<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostcodeService
{
    public function geocode(string $postcode): ?array
    {
        try {
            $response = Http::baseUrl(config('services.postcodes.url'))
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->get('/postcodes/'.rawurlencode($postcode));
        } catch (ConnectionException $e) {
            Log::warning('Postcode service unreachable', ['postcode' => $postcode, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            if ($response->status() !== 404) {
                Log::warning('Postcode lookup failed', ['postcode' => $postcode, 'status' => $response->status()]);
            }

            return null;
        }

        $result = $response->json('result');

        if (! is_array($result)) {
            return null;
        }

        return [
            'postcode' => $result['postcode'],
            'area_name' => $result['admin_district'] ?? null,
            'latitude' => $result['latitude'],
            'longitude' => $result['longitude'],
        ];
    }
}
