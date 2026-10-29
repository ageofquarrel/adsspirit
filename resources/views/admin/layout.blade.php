<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Заказы')  — MarketHub Admin</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            background: #f3f4f6;
            color: #111827;
        }
        header {
            background: #111827;
            color: #fff;
            padding: 0.9rem 1.5rem;
        }
        header a { color: #fff; text-decoration: none; font-weight: 600; }
        main { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .card {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
        }
        .card h2 { margin-top: 0; font-size: 1rem; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.55rem 0.7rem; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 0.9rem; }
        th { background: #f9fafb; font-weight: 600; color: #374151; }
        tbody tr:hover td { background: #fafafa; }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .badge-created { background: #6b7280; }
        .badge-paid { background: #2563eb; }
        .badge-shipped { background: #7c3aed; }
        .badge-delivered { background: #059669; }
        .badge-cancelled { background: #dc2626; }
        form.filters { display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; margin-bottom: 1.25rem; }
        form.filters label { display: block; font-size: 0.78rem; color: #4b5563; margin-bottom: 0.25rem; }
        form.filters input, form.filters select {
            padding: 0.4rem 0.55rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }
        form.filters button {
            padding: 0.45rem 1.1rem;
            border: 0;
            border-radius: 0.375rem;
            background: #111827;
            color: #fff;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .pagination { margin-top: 1rem; display: flex; gap: 1.25rem; align-items: center; font-size: 0.9rem; color: #4b5563; }
        .pagination a { color: #2563eb; text-decoration: none; }
        a.back { color: #2563eb; text-decoration: none; font-size: 0.9rem; }
        dl { display: grid; grid-template-columns: 160px 1fr; gap: 0.4rem 0.75rem; margin: 0; }
        dt { color: #6b7280; }
        .timeline { list-style: none; padding: 0; margin: 0; }
        .timeline li { padding: 0.45rem 0; border-bottom: 1px dashed #e5e7eb; }
        .timeline li:last-child { border-bottom: none; }
        .empty { color: #6b7280; padding: 1rem 0; }
    </style>
</head>
<body>
<header>
    <a href="{{ route('admin.orders.index') }}">MarketHub — Заказы</a>
</header>
<main>
    @yield('content')
</main>
</body>
</html>
