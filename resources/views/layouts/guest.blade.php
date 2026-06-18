<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SaaS Starter')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4ff; min-height: 100vh; }
        .brand-logo { font-size: 1.5rem; font-weight: 700; color: #4f46e5; text-decoration: none; }
        .brand-logo span { color: #1e1b4b; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 4px 24px rgba(79,70,229,.08); }
        .btn-primary { background: #4f46e5; border-color: #4f46e5; }
        .btn-primary:hover { background: #4338ca; border-color: #4338ca; }
        .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 .2rem rgba(79,70,229,.15); }
        .alert-danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    </style>
    @stack('head')
</head>
<body class="d-flex flex-column">
    <nav class="navbar navbar-light bg-white border-bottom py-3">
        <div class="container">
            <a href="/" class="brand-logo"><i class="bi bi-layers-fill me-1"></i>SaaS<span>Starter</span></a>
            @yield('nav-right')
        </div>
    </nav>

    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="text-center text-muted py-4 small">
        &copy; {{ date('Y') }} SaaSStarter. Built with Laravel &amp; Bootstrap.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
