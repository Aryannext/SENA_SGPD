<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SGPD SENA — Sistema de Gestión de Progreso y Desempeño Académico">
    <meta name="robots" content="noindex, nofollow">
    <meta name="base-path" content="<?= htmlspecialchars(\Core\App::basePath(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(\Core\Auth::tokenCsrf(), ENT_QUOTES, 'UTF-8') ?>">
    <title>SGPD SENA — <?= htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Design System -->
    <link rel="stylesheet" href="<?= \Core\App::url('/public/css/main.css') ?>">
    <?php if (!empty($extraCss)): ?>
        <?php foreach ((array) $extraCss as $css): ?>
            <link rel="stylesheet" href="<?= \Core\App::url('/public/css/') . $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <!-- Marked.js (Markdown parser) -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- DOMPurify: sanea el HTML generado por la IA antes de renderizarlo -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.1.6/purify.min.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <?php $isWidget = isset($_GET['widget']) && $_GET['widget'] === 'true'; ?>
        
        <?php if (!$isWidget): ?>
            <!-- Sidebar -->
            <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="main-content" <?= $isWidget ? 'style="margin-left:0;padding:0;"' : '' ?>>
            <?php if (!$isWidget): ?>
                <!-- Header -->
                <?php require __DIR__ . '/../partials/header.php'; ?>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="page-content" <?= $isWidget ? 'style="padding:0;max-width:none;"' : '' ?>>
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div class="toast-container" id="toast-container"></div>

    <?php if (!$isWidget): ?>
        <!-- Chat Widget (floating bubble) -->
        <?php require __DIR__ . '/../partials/chat_widget.php'; ?>
    <?php endif; ?>

    <!-- Core JS -->
    <script src="<?= \Core\App::url('/public/js/app.js') ?>"></script>
    <?php if (!empty($extraJs)): ?>
        <?php foreach ((array) $extraJs as $js): ?>
            <script src="<?= \Core\App::url('/public/js/') . $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
