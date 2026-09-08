<?php
/**
 * Layout: Minimal Shell — Logo-only Header with <main>
 *
 * A stripped-down layout intended for pages that need a custom or embedded
 * header but still want the standard <main> content wrapper. Current use case:
 * authentication and utility pages (login, password reset, register) where the
 * full nav and role-aware header would be inappropriate.
 *
 * Differences from base.php:
 * - Header is a minimal top-bar containing only the AMU logo — no nav, no
 *   language switcher, no login/logout button, no tritanopia toggle.
 * - No chatbot.php include.
 * - No chatbot.js script.
 * - No $activeMenu class on <main> (the element has no class attribute).
 * - $metaDescription uses !empty() guard (same as base_superadmin.php),
 *   so an empty string suppresses the meta tag.
 *
 * ── SESSION FLASH MESSAGE ────────────────────────────────────────────────────
 * Shown inside <main> in a .message div; session key is immediately unset.
 *
 * ── SCRIPTS ──────────────────────────────────────────────────────────────────
 * Only js/main.js is loaded unconditionally. Additional $scripts follow.
 *
 * @var string        $lang            Current language code (e.g. 'fr' or 'en')
 * @var string        $title           Full HTML <title> text for the page
 * @var string        $content         Pre-rendered HTML string from the view's ob_get_clean()
 * @var array<string> $styles          Additional stylesheet paths to link
 * @var array<string> $scripts         Additional script paths to load after main.js
 * @var string        $userRole        Role of the current user; passed through to footer.php
 * @var string|null   $metaDescription Optional meta description; omitted when null or empty string
 */

$isTritanopia = isset($_SESSION['tritanopia']) && $_SESSION['tritanopia'] === true;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if (!empty($metaDescription)): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>

    <title><?= htmlspecialchars($title) ?></title>

    <!-- Styles de base -->
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/chatbot.css">
    <link rel="icon" type="image/png" href="img/favicon.webp"/>

    <!-- Styles additionnels -->
    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>

<body class="<?= $isTritanopia ? 'tritanopie' : '' ?>">

<header>
    <div class="top-bar">
        <img class="logo_amu" src="img/logo.png" alt="Logo AMU">
    </div>
</header>

<main>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message">
            <?= htmlspecialchars((string) $_SESSION['message']); ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Scripts de base -->
<script src="js/main.js"></script>

<!-- Scripts additionnels -->
<?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>