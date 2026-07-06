<?php

namespace Tests\Feature;

use App\Livewire\AreaRiskSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AreaRiskSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApis(): void
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
            'data.police.uk/*' => Http::response([
                ['category' => 'violent-crime', 'month' => '2026-04', 'outcome_status' => ['category' => 'Under investigation']],
                ['category' => 'burglary', 'month' => '2026-04', 'outcome_status' => null],
            ]),
        ]);
    }

    public function test_a_valid_postcode_looks_up_and_records_a_snapshot(): void
    {
        $this->fakeApis();

        Livewire::test(AreaRiskSnapshot::class)
            ->set('postcode', 'sw1a 1aa')
            ->call('lookup')
            ->assertHasNoErrors()
            ->assertSee('Westminster')
            ->assertSee('Reported outcomes');

        $this->assertDatabaseHas('lookups', [
            'postcode' => 'SW1A 1AA',
            'area_name' => 'Westminster',
            'total_crimes' => 2,
            'top_category' => 'violent-crime',
        ]);
    }

    public function test_an_invalid_postcode_format_is_rejected_without_a_lookup(): void
    {
        Livewire::test(AreaRiskSnapshot::class)
            ->set('postcode', 'not a postcode')
            ->call('lookup')
            ->assertHasErrors('postcode');

        $this->assertDatabaseCount('lookups', 0);
    }

    public function test_a_wellformed_but_unresolvable_postcode_fails_soft(): void
    {
        Http::fake(['api.postcodes.io/*' => Http::response(['status' => 404], 404)]);

        Livewire::test(AreaRiskSnapshot::class)
            ->set('postcode', 'ZZ99 9ZZ')
            ->call('lookup')
            ->assertHasErrors('postcode');

        $this->assertDatabaseCount('lookups', 0);
    }
}
