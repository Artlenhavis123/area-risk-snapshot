<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Area Risk Snapshot' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes snapshot-reveal {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .snapshot-reveal { animation: snapshot-reveal 0.28s ease-out both; }
        @media (prefers-reduced-motion: reduce) {
            .snapshot-reveal { animation: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-[#EAEEF1] text-[#171E27]">
    <main class="mx-auto max-w-2xl px-5 py-14">
        {{ $slot }}
    </main>
</body>
</html>
