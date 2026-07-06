# Area Risk Snapshot

Enter a UK postcode and get a plain, honest summary of recently reported
street-level crime around that location - total incidents, a ranked breakdown
by category, and what happened to those reports - built on open data from
[postcodes.io](https://postcodes.io) and [police.uk](https://data.police.uk).

Built as a technical exercise with Laravel + Livewire.

## What it does

- Validates and geocodes a UK postcode via **postcodes.io** (no API key).
- Fetches street-level crime for that point from the **police.uk** API and
  aggregates it into a total, a category breakdown, and an outcomes breakdown.
- Presents it as a descriptive snapshot - **not** a risk score (see below).
- Persists each lookup and shows a short "recent lookups" list.
- Exposes the same lookup from the command line:
  `php artisan area:snapshot "SW1A 1AA"`.

## Why this API

NetWatch lists **Police Report Retrieval** as a named service and has written
about surfacing police turnaround data in Rapid, so UK police open data felt
like genuinely on-theme subject matter rather than an arbitrary dataset. Both
postcodes.io and police.uk are completely free with no key or registration,
which keeps the project self-contained and reproducible.

## Requirements

- PHP 8.4+
- Composer
- Node 20+ and npm

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate

# SQLite is used by default - create the database file and migrate
touch database/database.sqlite
php artisan migrate

# Build front-end assets (or `npm run dev` while developing)
npm install
npm run build

php artisan serve
```

Then open http://localhost:8000. No API keys or `.env` changes are needed -
the two upstream APIs are public, and their base URLs live in
`config/services.php` with sensible defaults.

## Running the tests

```bash
php artisan test
```

The suite uses `Http::fake()` so it never touches the live APIs. It covers the
category and outcome aggregation, the empty-area case, and the fail-soft
behaviour when an upstream API errors.

## Design notes

The interesting part of this brief isn't calling two REST endpoints - it's
handling external data you don't control, cleanly and honestly.

- **Service layer.** `PostcodeService` and `PoliceDataService` own all upstream
  interaction and aggregation. The Livewire component and the Artisan command
  are both thin callers, which is why the same logic drives the web UI and the
  CLI with no duplication.
- **Fail-soft by design.** An unknown postcode, a rural area with no crime, or
  an upstream outage all resolve to a sensible result rather than an error.
  `PostcodeService` returns `null`; `PoliceDataService` distinguishes "API
  unreachable" (`null`, uncached) from "genuinely quiet area" (empty, cached).
- **Resilient HTTP.** Both calls set a timeout and retry transient failures,
  and log a warning when they fail - except an expected 404, which is a normal
  outcome, not a fault.
- **Caching.** Crime results are cached per location for 6 hours (police data
  only updates monthly). Failed responses are deliberately *not* cached, so a
  transient blip doesn't get stuck for six hours.
- **Rate limiting.** The web lookup is capped at 10/minute per IP. This guards
  the public entry point; the cache guards the upstream API from repeat load -
  two different controls for two different concerns.
- **Validation.** Postcode format is validated by a dedicated form request,
  reused as the single source of truth by the Livewire component.
- **Honest presentation.** The summary is a descriptive count, and outcomes
  that police.uk doesn't record (anti-social behaviour and the most recent
  reports carry no outcome) are surfaced as an explicit "not recorded" figure
  rather than hidden or bucketed as if they were a real outcome.

## Trade-offs and limitations

- **Not a risk score.** The app deliberately avoids dressing a raw incident
  count up as a validated risk model - that would undercut the point. It
  describes; it doesn't score.
- **Data freshness and precision.** police.uk street-level data is typically a
  couple of months behind and snaps to roughly a one-mile area around the
  point. Both are properties of the source, surfaced plainly in the UI.
- **No auth or multi-tenancy.** The tool is a single public page; user
  accounts, saved areas, and per-client data were out of scope here.
- **Cache key is exact.** Two nearby postcodes with slightly different
  centroids cache separately; rounding the coordinates would raise the hit rate.

## What I'd add with more time

- Month-over-month comparison (the API accepts a date), to show whether an area
  is trending up or down rather than a single snapshot.
- Compare two postcodes side by side - a realistic due-diligence use case.
- A small map of the incident spread, and richer per-category context.
- Contract tests that periodically check the live API response shape hasn't
  drifted, complementing the faked unit tests.
