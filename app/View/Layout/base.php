<?php
/**
 * Layout: Base — Primary Application Shell
 *
 * The default layout used by every authenticated page that needs the standard
 * header and navigation. Views render their HTML into $content via ob_start() /
 * ob_get_clean() and then include this file to compose the full document.
 *
 * ── <head> ───────────────────────────────────────────────────────────────────
 * Always loads styles/index.css and styles/chatbot.css unconditionally, then
 * any additional stylesheets listed in $styles. The page title and lang
 * attribute are set from $title and $lang respectively.
 *
 * ── <body> CLASS ─────────────────────────────────────────────────────────────
 * Two optional classes are applied:
 * - 'tritanopie'  : added when $_SESSION['tritanopia'] === true.
 * - 'home-page'   : added when $noMain is true (the layout is being used in
 *                   no-<main> mode, e.g. for full-viewport dashboard shells).
 *
 * ── CONTENT WRAPPER ──────────────────────────────────────────────────────────
 * Behaviour is controlled by $noMain (defaulting to false):
 *
 * $noMain = false (default):
 *   $content is rendered inside <main class="{$activeMenu}">.
 *   The CSS class on <main> lets pages target their own scope without
 *   extra wrapper divs. Session flash messages are shown inside <main>
 *   in a .message div; the session key is immediately unset after display.
 *
 * $noMain = true:
 *   <main> is omitted entirely. $content is rendered directly after the
 *   header. Session flash messages are still shown, but with inline styles
 *   (max-width:1200px; margin:20px auto) to constrain width without <main>.
 *   Used for full-viewport layouts (e.g. the Outlook-style messages inbox).
 *
 * ── CHATBOT ──────────────────────────────────────────────────────────────────
 * chatbot.php is included UNLESS $userRole is one of: 'coordinateur',
 * 'coordinateur_etude', 'coordinateur_stage', or 'chef_departement'.
 * The chatbot is intentionally hidden for these roles.
 *
 * ── SCRIPTS ──────────────────────────────────────────────────────────────────
 * js/main.js and js/chatbot.js are always loaded. Additional scripts from
 * $scripts are appended after them so page-specific code can rely on both.
 *
 * @var string             $lang        Current language code (e.g. 'fr' or 'en')
 * @var string             $title       Full HTML <title> text for the page
 * @var string             $content     Pre-rendered HTML string from the view's ob_get_clean()
 * @var array<string>      $styles      Additional stylesheet paths to link (relative to document root)
 * @var array<string>      $scripts     Additional script paths to load after main.js and chatbot.js
 * @var string             $activeMenu  Identifier for the active nav item; also used as the <main> CSS class
 * @var string             $userRole    Role of the current user — controls chatbot visibility and nav rendering in header.php
 * @var bool|null          $noMain      When true, omits the <main> wrapper and applies the 'home-page' body class; defaults to false
 * @var Closure(array<string, string>): string $t        Translation callable passed through to included partials (header.php, footer.php, chatbot.php)
 * @var Closure(string, array<string, mixed>=): string $buildUrl URL builder callable passed through to included partials
 */

$isTritanopia = isset($_SESSION['tritanopia']) && $_SESSION['tritanopia'] === true;
$noMain       = $noMain ?? false;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>

    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="styles/chatbot.css">
    <link rel="icon" type="image/png" href="img/favicon.webp"/>

    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= $isTritanopia ? 'tritanopie' : '' ?><?= $noMain ? ' home-page' : '' ?>">

<?php include __DIR__ . '/header.php'; ?>

<?php if ($noMain): ?>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="message" style="max-width:1200px;margin:20px auto;">
            <?= htmlspecialchars((string) $_SESSION['message']); ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?= $content ?>

<?php else: ?>

    <main class="<?= $activeMenu ?>">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message">
                <?= htmlspecialchars((string) $_SESSION['message']); ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

<?php endif; ?>

<?php if (!in_array($userRole, ['coordinateur', 'coordinateur_etude', 'coordinateur_stage', 'chef_departement'])): ?>
    <?php include __DIR__ . '/chatbot.php'; ?>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>

<script src="js/main.js"></script>
<?php if ($userRole === 'admin'): ?>
    <script src="js/chatbot-admin.js"></script>
<?php elseif ($userRole === 'student'): ?>
    <script src="js/chatbot-student.js"></script>
<?php endif; ?>
<?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>