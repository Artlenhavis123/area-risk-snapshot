<div class="space-y-10">
    <header class="space-y-3">
        <p class="font-mono text-[11px] font-medium uppercase tracking-[0.18em] text-[#1F5C8B]">
            Street-level crime · police.uk open data
        </p>
        <h1 class="text-[28px] font-semibold leading-tight tracking-tight">Area Risk Snapshot</h1>
        <p class="max-w-lg text-sm leading-relaxed text-[#5B6675]">
            Enter a UK postcode for a plain summary of recently reported street-level
            crime around that location.
        </p>
    </header>

    <form wire:submit="lookup" class="space-y-2">
        <label for="postcode" class="mb-1.5 block font-mono text-[11px] font-medium uppercase tracking-[0.14em] text-[#5B6675]">
            Postcode
        </label>
        <div class="flex gap-2">
            <input
                id="postcode"
                type="text"
                wire:model="postcode"
                placeholder="SW1A 1AA"
                autofocus
                autocomplete="off"
                class="flex-1 rounded-md border border-[#CBD4DC] bg-white px-3.5 py-2.5 font-mono text-sm uppercase tracking-wide text-[#171E27] placeholder:text-[#AAB4BF] placeholder:normal-case focus:border-[#1F5C8B] focus:outline-none focus:ring-2 focus:ring-[#1F5C8B]/20"
            >
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="lookup"
                class="shrink-0 rounded-md bg-[#1F5C8B] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#184a71] focus:outline-none focus:ring-2 focus:ring-[#1F5C8B]/30 disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="lookup">Look up</span>
                <span wire:loading wire:target="lookup">Checking…</span>
            </button>
        </div>
        @error('postcode')
            <p class="pt-0.5 text-sm text-[#B4472B]">{{ $message }}</p>
        @enderror
    </form>

    @if ($result)
        @php
            $summary = $result['summary'];
            $location = $result['location'];
            $max = $summary['total'] > 0 ? max($summary['categories']) : 1;
        @endphp

        <section class="snapshot-reveal overflow-hidden rounded-xl border border-[#D3DBE2] bg-white shadow-[0_1px_2px_rgba(23,30,39,0.04),0_8px_24px_-12px_rgba(23,30,39,0.12)]">
            <div class="flex items-center justify-between border-b border-[#E4E9EE] bg-[#F7F9FB] px-6 py-3">
                <span class="font-mono text-[11px] font-medium uppercase tracking-[0.16em] text-[#5B6675]">Area snapshot</span>
                @if ($summary['data_month'])
                    <span class="font-mono text-[11px] font-medium uppercase tracking-[0.16em] text-[#1F5C8B]">
                        {{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $summary['data_month'])->translatedFormat('M Y')) }}
                    </span>
                @endif
            </div>

            <div class="px-6 py-6">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 class="text-xl font-semibold tracking-tight">{{ $location['area_name'] ?? $location['postcode'] }}</h2>
                    <span class="font-mono text-sm text-[#5B6675]">{{ $location['postcode'] }}</span>
                </div>
                <p class="mt-1 font-mono text-[11px] uppercase tracking-[0.1em] text-[#9AA5B1]">
                    Centroid {{ number_format($location['latitude'], 4) }}, {{ number_format($location['longitude'], 4) }}
                </p>

                @if ($summary['total'] === 0)
                    <p class="mt-5 text-sm leading-relaxed text-[#5B6675]">
                        No street-level crimes were reported near this postcode in the latest available
                        data. For rural areas this is a normal result, not an error.
                    </p>
                @else
                    <p class="mt-5 text-[15px] leading-relaxed text-[#2C3642]">
                        <span class="font-semibold text-[#171E27]">{{ number_format($summary['total']) }}</span>
                        crimes were reported near this postcode, most commonly
                        <span class="font-medium text-[#171E27]">{{ \Illuminate\Support\Str::headline($summary['top_category']) }}</span>.
                    </p>

                    <ul class="mt-6 space-y-2.5">
                        @foreach ($summary['categories'] as $category => $count)
                            <li class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-1">
                                <span class="text-sm text-[#2C3642]">{{ \Illuminate\Support\Str::headline($category) }}</span>
                                <span class="font-mono text-sm tabular-nums text-[#5B6675]">{{ number_format($count) }}</span>
                                <span class="col-span-2 h-1.5 overflow-hidden rounded-full bg-[#EDF1F4]">
                                    <span
                                        class="block h-full rounded-full {{ $loop->first ? 'bg-[#1F5C8B]' : 'bg-[#1F5C8B]/45' }}"
                                        style="width: {{ max(3, round(($count / $max) * 100)) }}%"
                                    ></span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <p class="mt-6 border-t border-[#EDF1F4] pt-4 text-xs leading-relaxed text-[#9AA5B1]">
                    A descriptive count of reported incidents, not a risk score. Police street-level
                    data is typically a couple of months behind and covers roughly a one-mile area
                    around the postcode.
                </p>
            </div>
        </section>
    @endif

    @if ($this->recent->isNotEmpty())
        <section class="space-y-3">
            <h2 class="font-mono text-[11px] font-medium uppercase tracking-[0.16em] text-[#5B6675]">Recent lookups</h2>
            <ul class="divide-y divide-[#E4E9EE] overflow-hidden rounded-xl border border-[#D3DBE2] bg-white">
                @foreach ($this->recent as $lookup)
                    <li class="flex items-center justify-between px-5 py-3">
                        <span class="font-mono text-sm font-medium tracking-wide text-[#171E27]">{{ $lookup->postcode }}</span>
                        <span class="text-sm text-[#5B6675]">
                            {{ $lookup->area_name }}
                            <span class="font-mono tabular-nums text-[#9AA5B1]">· {{ number_format($lookup->total_crimes) }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
