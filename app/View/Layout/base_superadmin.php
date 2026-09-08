<?php
/**
 * Layout: Super Admin Shell
 *
 * A self-contained layout for the super-admin page. Unlike base.php it does
 * not include header.php; instead it renders its own simplified top bar with
 * only the AMU logo, a language switcher, a login/logout button, and the
 * tritanopia toggle. No navigation menu is rendered.
 *
 * ── SELF-CONTAINED HELPERS ───────────────────────────────────────────────────
 * $t and $buildUrl are defined locally at the top of this file rather than
 * being injected by a controller. This makes the layout self-sufficient and
 * independent of whatever scope the including view runs in:
 * - $t          : returns $frEn['en'] when $lang === 'en', else $frEn['fr'].
 * - $buildUrl   : appends lang={$lang} to the given path; uses '?' or '&'
 *                 depending on whether the path already contains a query string.
 * These local definitions are also available to footer.php via include scope.
 *
 * ── <head> ───────────────────────────────────────────────────────────────────
 * Always loads styles/index.css and styles/chatbot.css, then any additional
 * stylesheets from $styles. An optional <meta name="description"> tag is
 * rendered when $metaDescription is non-empty.
 *
 * ── <body> CLASS ─────────────────────────────────────────────────────────────
 * 'tritanopie' is added when $_SESSION['tritanopia'] === true. No 'home-page'
 * class is applied (the layout always wraps content in <main>).
 *
 * ── TOP BAR ──────────────────────────────────────────────────────────────────
 * Language switcher links are hardcoded to ?page=super-admin&lang=fr / en.
 * Login/logout is determined by isset($_SESSION['role']) ($isLoggedIn).
 * The tritanopia toggle (#theme-toggle) is always visible.
 *
 * ── CONTENT WRAPPER ──────────────────────────────────────────────────────────
 * $content is always rendered inside <main>. Session flash messages are shown
 * in a .message div and immediately unset from the session.
 *
 * ── SCRIPTS ──────────────────────────────────────────────────────────────────
 * Only js/main.js is loaded unconditionally (no chatbot.js — the chatbot is
 * not included for this layout). Additional $scripts are appended after it.
 *
 * @var string        $lang            Current language code (e.g. 'fr' or 'en')
 * @var string        $title           Full HTML <title> text for the page
 * @var string        $content         Pre-rendered HTML string from the view's ob_get_clean()
 * @var array<string> $styles          Additional stylesheet paths to link
 * @var array<string> $scripts         Additional script paths to load after main.js
 * @var string        $userRole        Role of the current user (expected 'super_admin')
 * @var string|null   $metaDescription Optional meta description string; omitted when empty
 */

$isTritanopia = isset($_SESSION['tritanopia']) && $_SESSION['tritanopia'] === true;
$isLoggedIn   = isset($_SESSION['role']);

$t = function (array $frEn) use ($lang): string {
    return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
};

$buildUrl = function (string $path, array $params = []) use ($lang): string {
    $params['lang'] = $lang;
    $separator = (strpos($path, '?') === false) ? '?' : '&';
    return $path . $separator . http_build_query($params);
};
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

    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/chatbot.css">
    <link rel="icon" type="image/png" href="img/favicon.webp"/>

    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>

<body class="<?= $isTritanopia ? 'tritanopie' : '' ?>">

<header>
    <div class="top-bar">
        <img class="logo_amu" src="img/logo.png" alt="Logo AMU">

        <div class="right-buttons">
            <div class="lang-dropdown">
                <button class="dropbtn"><?= htmlspecialchars($lang) ?></button>
                <div class="dropdown-content">
                    <a href="?page=super-admin&lang=fr">Français</a>
                    <a href="?page=super-admin&lang=en">English</a>
                </div>
            </div>

            <?php if ($isLoggedIn): ?>
                <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'logout']) ?>'">
                    <?= $t(['fr' => 'Se déconnecter', 'en' => 'Log out']) ?>
                </button>
            <?php else: ?>
                <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'login']) ?>'">
                    <?= $t(['fr' => 'Se connecter', 'en' => 'Log in']) ?>
                </button>
            <?php endif; ?>

            <button id="theme-toggle" title="Enable tritanopia accessibility mode">
                <span class="toggle-switch"></span>
            </button>
        </div>
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

<script src="js/main.js"></script>

<?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>