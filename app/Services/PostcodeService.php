<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PostcodeService
{
    public function geocode(string $postcode): ?array
    {
        $response = Http::get('https://api.postcodes.io/postcodes/'.rawurlencode($postcode));

        if (! $response->successful()) {
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
