<?php

namespace Tests\Feature\Services;

use App\Services\PoliceDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PoliceDataServiceTest extends TestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleCrimes(): array
    {
        return [
            ['category' => 'violent-crime', 'month' => '2026-04', 'outcome_status' => ['category' => 'Under investigation']],
            ['category' => 'violent-crime', 'month' => '2026-04', 'outcome_status' => ['category' => 'Under investigation']],
            ['category' => 'burglary', 'month' => '2026-04', 'outcome_status' => ['category' => 'Investigation complete; no suspect identified']],
            ['category' => 'anti-social-behaviour', 'month' => '2026-04', 'outcome_status' => null],
        ];
    }

    public function test_it_aggregates_categories_sorted_by_frequency(): void
    {
        Http::fake(['data.police.uk/*' => Http::response($this->sampleCrimes())]);

        $summary = app(PoliceDataService::class)->getCrimeSummary(51.5, -0.14);

        $this->assertSame(4, $summary['total']);
        $this->assertSame('violent-crime', $summary['top_category']);
        $this->assertSame(
            ['violent-crime' => 2, 'burglary' => 1, 'anti-social-behaviour' => 1],
            $summary['categories'],
        );
        $this->assertSame('2026-04', $summary['data_month']);
    }

    public function test_it_aggregates_outcomes_and_keeps_the_not_recorded_count_separate(): void
    {
        Http::fake(['data.police.uk/*' => Http::response($this->sampleCrimes())]);

        $summary = app(PoliceDataService::class)->getCrimeSummary(51.5, -0.14);

        $this->assertSame([
            'Under investigation' => 2,
            'Investigation complete; no suspect identified' => 1,
        ], $summary['outcomes']);

        $this->assertSame(1, $summary['outcomes_not_recorded']);
    }

    public function test_an_empty_area_returns_a_zero_filled_summary(): void
    {
        Http::fake(['data.police.uk/*' => Http::response([])]);

        $summary = app(PoliceDataService::class)->getCrimeSummary(57.0, -5.0);

        $this->assertSame(0, $summary['total']);
        $this->assertSame([], $summary['categories']);
        $this->assertNull($summary['top_category']);
        $this->assertSame([], $summary['outcomes']);
        $this->assertSame(0, $summary['outcomes_not_recorded']);
        $this->assertNull($summary['data_month']);
    }

    public function test_it_fails_soft_on_an_api_error_and_does_not_cache_the_failure(): void
    {
        Http::fake(['data.police.uk/*' => Http::response('upstream error', 500)]);

        $summary = app(PoliceDataService::class)->getCrimeSummary(51.5, -0.14);

        $this->assertSame(0, $summary['total']);
        $this->assertFalse(Cache::has('crimes:51.5:-0.14'));
    }
}
