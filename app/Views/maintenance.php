<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Maintenance | Jam with Jamie</title>
    <link rel="icon" type="image/png" href="<?= base_url('img/logos/icon.png') ?>">
    <style>
        :root {
            color-scheme: light;
            --pink: #f36cae;
            --pink-dark: #d7478e;
            --ink: #282134;
            --muted: #746d7c;
            --cream: #fffaf6;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 12% 18%, rgba(255, 205, 228, .72) 0 9%, transparent 28%),
                radial-gradient(circle at 88% 82%, rgba(255, 220, 167, .55) 0 10%, transparent 30%),
                var(--cream);
            color: var(--ink);
            font-family: Inter, ui-rounded, "Avenir Next", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page {
            width: min(92%, 720px);
            padding: 54px 24px;
            text-align: center;
            position: relative;
        }

        .logo {
            width: min(230px, 62vw);
            height: auto;
            margin-bottom: 34px;
        }

        .card {
            position: relative;
            padding: clamp(34px, 7vw, 62px);
            overflow: hidden;
            border: 1px solid rgba(40, 33, 52, .08);
            border-radius: 32px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 24px 70px rgba(83, 48, 72, .14);
            backdrop-filter: blur(12px);
        }

        .card::before,
        .card::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .card::before {
            width: 170px;
            height: 170px;
            top: -105px;
            right: -65px;
            background: rgba(243, 108, 174, .16);
        }

        .card::after {
            width: 90px;
            height: 90px;
            bottom: -54px;
            left: -32px;
            background: rgba(255, 198, 110, .22);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 22px;
            padding: 8px 14px;
            border-radius: 999px;
            background: #fff0f7;
            color: var(--pink-dark);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--pink);
            box-shadow: 0 0 0 6px rgba(243, 108, 174, .13);
            animation: pulse 2s ease-in-out infinite;
        }

        h1 {
            max-width: 560px;
            margin: 0 auto 18px;
            font-size: clamp(36px, 7vw, 60px);
            line-height: 1.02;
            letter-spacing: -.045em;
        }

        p {
            max-width: 520px;
            margin: 0 auto;
            color: var(--muted);
            font-size: clamp(16px, 2.4vw, 19px);
            line-height: 1.7;
        }

        button {
            margin-top: 30px;
            padding: 14px 24px;
            border: 0;
            border-radius: 999px;
            background: var(--ink);
            color: white;
            cursor: pointer;
            font: inherit;
            font-weight: 750;
            box-shadow: 0 10px 24px rgba(40, 33, 52, .18);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        button:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(40, 33, 52, .23); }
        button:focus-visible { outline: 3px solid rgba(243, 108, 174, .45); outline-offset: 4px; }

        .note {
            margin-top: 24px;
            color: #958d9b;
            font-size: 13px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(.78); opacity: .62; }
        }

        @media (prefers-reduced-motion: reduce) {
            .status-dot { animation: none; }
            button { transition: none; }
        }

        @media (max-width: 520px) {
            .page { padding: 32px 16px; }
            .card { border-radius: 24px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <img class="logo" src="<?= base_url('img/logos/JWJ_logo-05.png') ?>" alt="Jam with Jamie">
        <section class="card" aria-labelledby="maintenance-title">
            <div class="status"><span class="status-dot" aria-hidden="true"></span> Brief intermission</div>
            <h1 id="maintenance-title">We&rsquo;re making things even more magical</h1>
            <p>Our booking experience is temporarily unavailable while we make a few improvements. We&rsquo;ll be back soon!</p>
            <button type="button" onclick="window.location.reload()">Try again</button>
            <div class="note">Thank you for your patience — the fun will be back soon.</div>
        </section>
    </main>
</body>
</html>
