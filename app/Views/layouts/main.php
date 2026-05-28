<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scal-e CDP</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <meta name="api-key" content="<?= htmlspecialchars(\App\Core\Env::get('API_KEY') ?? '', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <header class="header">
        <div class="container">
            <a class="logo" href="/" aria-label="Scal-e CDP home">
                <img src="/assets/images/logo.png" alt="Scal-e CDP">
            </a>
            <nav>
                <a href="/">Dashboard</a>
                <a href="/customers">Customers</a>
                <a href="/segments">Segments</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?= $content ?>
    </main>

    <script src="/assets/js/app.js"></script>
</body>
</html>
