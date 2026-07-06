<?php

namespace App\Console\Commands;

use App\Services\PoliceDataService;
use App\Services\PostcodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AreaSnapshotCommand extends Command
{
    protected $signature = 'area:snapshot {postcode : UK postcode to look up}';

    protected $description = 'Show a street-level crime snapshot for a UK postcode';

    public function handle(PostcodeService $postcodes, PoliceDataService $police): int
    {
        $postcode = Str::upper(trim($this->argument('postcode')));

        $location = $postcodes->geocode($postcode);

        if ($location === null) {
            $this->components->error("Couldn't resolve postcode: {$postcode}");

            return self::FAILURE;
        }

        $summary = $police->getCrimeSummary($location['latitude'], $location['longitude']);

        $this->newLine();
        $this->line("  <options=bold>{$location['area_name']}</> <fg=gray>({$location['postcode']}, {$summary['data_month']})</>");
        $this->newLine();

        if ($summary['total'] === 0) {
            $this->components->info('No street-level crimes reported in the latest data.');

            return self::SUCCESS;
        }

        $this->line("  <options=bold>{$summary['total']}</> crimes reported, most commonly ".Str::headline($summary['top_category']).'.');
        $this->newLine();

        $this->table(
            ['Category', 'Count'],
            collect($summary['categories'])
                ->map(fn (int $count, string $category) => [Str::headline($category), number_format($count)])
                ->values()
                ->all(),
        );

        return self::SUCCESS;
    }
}
