<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>SGPD SENA — <?= htmlspecialchars($pageTitle ?? 'Acceso', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/SENA_SGPD/public/css/main.css">
</head>
<body>
    <?= $content ?? '' ?>
    <div class="toast-container" id="toast-container"></div>
</body>
</html>
