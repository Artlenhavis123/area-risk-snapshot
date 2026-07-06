<?php

namespace Tests\Feature\Services;

use App\Services\PostcodeService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostcodeServiceTest extends TestCase
{
    public function test_it_maps_a_successful_response_to_a_location(): void
    {
        Http::fake([
            'api.postcodes.io/*' => Http::response([
                'status' => 200,
                'result' => [
                    'postcode' => 'SW1A 1AA',
                    'admin_district' => 'Westminster',
                    'latitude' => 51.501009,
                    'longitude' => -0.141588,
                ],
            ]),
        ]);

        $location = app(PostcodeService::class)->geocode('SW1A 1AA');

        $this->assertSame([
            'postcode' => 'SW1A 1AA',
            'area_name' => 'Westminster',
            'latitude' => 51.501009,
            'longitude' => -0.141588,
        ], $location);
    }

    public function test_it_returns_null_for_an_unknown_postcode(): void
    {
        Http::fake([
            'api.postcodes.io/*' => Http::response(['status' => 404, 'error' => 'Postcode not found'], 404),
        ]);

        $this->assertNull(app(PostcodeService::class)->geocode('ZZ99 9ZZ'));
    }
}
