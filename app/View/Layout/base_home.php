<?php

/**
 * Layout: Home Page Shell
 *
 * A specialised layout for the public and authenticated home pages
 * (home_student.php, home_admin.php, home_coordinateur.php). It differs from
 * base.php in two important ways:
 *
 * 1. NO <header> INCLUDE — the header (logo, language switcher, tritanopia
 *    toggle, login/logout, nav bar) is expected to be embedded directly inside
 *    $content by the view. This allows each home view to fully customise its
 *    own header markup and hero section layout.
 *
 * 2. NO <main> WRAPPER — $content is rendered directly into <body>. The body
 *    always carries the 'home-page' class (hardcoded, not conditional on
 *    $noMain). This enables full-viewport hero sections and scroll-based
 *    layouts that require the content to start at the top of the viewport
 *    without a containing block.
 *
 * ── <head> ───────────────────────────────────────────────────────────────────
 * Always loads styles/index.css and styles/chatbot.css, then any additional
 * stylesheets from $styles. $metaDescription is rendered as a <meta> tag
 * when not null (note: this layout uses !== null rather than !empty(), so an
 * empty string would still emit an empty meta tag — contrast with
 * base_superadmin.php and base_minimal.php which use !empty()).
 *
 * ── SESSION FLASH MESSAGE ────────────────────────────────────────────────────
 * Shown before $content with inline styles (max-width:1200px; margin:20px auto)
 * since there is no <main> to provide centering. The session key is immediately
 * unset after display.
 *
 * ── CHATBOT & FOOTER ─────────────────────────────────────────────────────────
 * chatbot.php and footer.php are always included (no role-gating). If the home
 * view is for a coordinator role, it should use base.php with $noMain = true
 * instead of this layout if the chatbot should be suppressed.
 *
 * ── SCRIPTS ──────────────────────────────────────────────────────────────────
 * js/main.js and js/chatbot.js are always loaded. Additional $scripts are
 * appended after them.
 *
 * @var string        $lang            Current language code (e.g. 'fr' or 'en')
 * @var string        $title           Full HTML <title> text for the page
 * @var string        $content         Pre-rendered HTML string from the view's ob_get_clean(); must include the page's own header markup
 * @var array<string> $styles          Additional stylesheet paths to link
 * @var array<string> $scripts         Additional script paths to load after main.js and chatbot.js
 * @var string        $userRole        Role of the current user; passed through to footer.php
 * @var string|null   $metaDescription Optional meta description; rendered when not null (including empty string)
 */

$isTritanopia = isset($_SESSION['tritanopia']) && $_SESSION['tritanopia'] === true;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if ($metaDescription !== null): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>

    <title><?= htmlspecialchars($title) ?></title>

    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/chatbot.css">
    <link rel="icon" type="image/png" href="img/favicon.webp"/>

    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>

<body class="<?= $isTritanopia ? 'tritanopie' : '' ?> home-page">

<?php if (isset($_SESSION['message'])): ?>
    <div class="message" style="max-width: 1200px; margin: 20px auto;">
        <?= htmlspecialchars((string) $_SESSION['message']); ?>
        <?php unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?= $content ?>

<?php include __DIR__ . '/chatbot.php'; ?>
<?php include __DIR__ . '/footer.php'; ?>


<script src="js/main.js"></script>
<script src="js/chatbot.js"></script>

<?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>