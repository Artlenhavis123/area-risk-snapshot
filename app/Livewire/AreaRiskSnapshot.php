<?php

namespace App\Livewire;

use App\Http\Requests\LookupPostcodeRequest;
use App\Models\Lookup;
use App\Services\PoliceDataService;
use App\Services\PostcodeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.snapshot')]
#[Title('Area Risk Snapshot')]
class AreaRiskSnapshot extends Component
{
    public string $postcode = '';

    public ?array $result = null;

    public function lookup(PostcodeService $postcodes, PoliceDataService $police): void
    {
        $this->postcode = strtoupper(trim($this->postcode));

        $this->validate(
            (new LookupPostcodeRequest)->rules(),
            (new LookupPostcodeRequest)->messages(),
        );

        $rateKey = 'lookup:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateKey, maxAttempts: 10)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $this->addError('postcode', "Too many lookups. Please wait {$seconds} seconds and try again.");

            return;
        }

        RateLimiter::hit($rateKey, decaySeconds: 60);

        $location = $postcodes->geocode($this->postcode);

        if ($location === null) {
            $this->addError('postcode', "We couldn't find that postcode. Please check and try again.");

            return;
        }

        $summary = $police->getCrimeSummary($location['latitude'], $location['longitude']);

        Lookup::create([
            'postcode' => $location['postcode'],
            'area_name' => $location['area_name'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'total_crimes' => $summary['total'],
            'top_category' => $summary['top_category'],
            'data_month' => $summary['data_month'],
        ]);

        $this->result = [
            'location' => $location,
            'summary' => $summary,
        ];

        unset($this->recent);
    }

    #[Computed]
    public function recent(): Collection
    {
        return Lookup::recent()->get();
    }
}
