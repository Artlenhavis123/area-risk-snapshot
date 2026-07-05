<div class="space-y-8">
    <header class="space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight">Area Risk Snapshot</h1>
        <p class="text-sm text-zinc-600">
            Enter a UK postcode to see a plain summary of recently reported street-level
            crime in that area, using open data from police.uk.
        </p>
    </header>

    <form wire:submit="lookup" class="space-y-2">
        <div class="flex gap-2">
            <input
                type="text"
                wire:model="postcode"
                placeholder="e.g. SW1A 1AA"
                autofocus
                class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500"
            >
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="lookup">Look up</span>
                <span wire:loading wire:target="lookup">Checking…</span>
            </button>
        </div>
        @error('postcode')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </form>

    @if ($result)
        @php
            $summary = $result['summary'];
            $location = $result['location'];
        @endphp

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-baseline justify-between">
                <h2 class="text-lg font-semibold">
                    {{ $location['area_name'] ?? $location['postcode'] }}
                </h2>
                <span class="text-sm text-zinc-500">{{ $location['postcode'] }}</span>
            </div>

            @if ($summary['total'] === 0)
                <p class="mt-3 text-sm text-zinc-600">
                    No street-level crimes were reported near this postcode in the latest
                    available data. For rural areas this is a normal result rather than an error.
                </p>
            @else
                <p class="mt-3 text-zinc-700">
                    <span class="font-semibold">{{ number_format($summary['total']) }}</span>
                    crimes were reported near this postcode in
                    <span class="font-medium">{{ \Carbon\Carbon::createFromFormat('Y-m', $summary['data_month'])->translatedFormat('F Y') }}</span>,
                    most commonly <span class="font-medium">{{ \Illuminate\Support\Str::headline($summary['top_category']) }}</span>.
                </p>

                <ul class="mt-5 space-y-2">
                    @foreach ($summary['categories'] as $category => $count)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-zinc-700">{{ \Illuminate\Support\Str::headline($category) }}</span>
                            <span class="tabular-nums text-zinc-500">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-5 border-t border-zinc-100 pt-4 text-xs text-zinc-400">
                This is a descriptive count of reported incidents, not a risk score. Police
                street-level data is typically a couple of months behind and covers roughly a
                one-mile area around the postcode.
            </p>
        </section>
    @endif

    @if ($this->recent->isNotEmpty())
        <section class="space-y-3">
            <h2 class="text-sm font-semibold text-zinc-500">Recent lookups</h2>
            <ul class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 bg-white">
                @foreach ($this->recent as $lookup)
                    <li class="flex items-center justify-between px-4 py-3 text-sm">
                        <span class="font-medium">{{ $lookup->postcode }}</span>
                        <span class="text-zinc-500">
                            {{ $lookup->area_name }} — {{ number_format($lookup->total_crimes) }} reported
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
