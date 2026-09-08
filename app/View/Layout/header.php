<?php
/**
 * Partial: Site Header — Top Bar and Navigation
 *
 * Renders the AMU logo, language switcher, login/logout button, optional
 * tritanopia toggle, and a role-aware navigation menu.
 *
 * ── PRE-RENDER SETUP ─────────────────────────────────────────────────────────
 * Three defensive initialisations run before any HTML is emitted:
 * 1. $userRole is filled from $_SESSION['role'] when the including layout
 *    has not already set it.
 * 2. $t is created as a simple closure if not already injected (or not
 *    callable). The fallback reads $lang from $_SESSION['lang'] (default 'fr')
 *    so the header can translate strings independently.
 * 3. $lang is set from $_SESSION['lang'] if not already set.
 *
 * $isCoordinateur is true for the roles: 'coordinateur_etude',
 * 'coordinateur_stage', 'chef_departement', and 'coordinateur'.
 *
 * $currentPage is determined from $_GET['page'], falling back to a
 * role-appropriate default: 'home-admin', 'home-coordinateur', or
 * 'home-student'. This is used for the language-switcher hrefs so the
 * language toggle lands back on the correct page.
 *
 * ── TOP BAR ──────────────────────────────────────────────────────────────────
 * - AMU logo (img/logo.png).
 * - Language dropdown: links to ?page={currentPage}&lang=fr / en.
 * - Login / logout button: shown only on home pages ($isHomePage is true when
 *   $activeMenu is 'home' or 'home-coordinateur'). Checks $_SESSION['role']
 *   to decide between "Se connecter" and "Se déconnecter".
 * - Tritanopia toggle button (#theme-toggle): shown only on home pages.
 *
 * ── NAVIGATION (<nav class="menu">) ──────────────────────────────────────────
 * Five distinct nav configurations based on $userRole:
 *
 * 'coordinateur' (super-coordinator):
 *   Home | Study Coordinator | Internship Coordinator
 *
 * 'coordinateur_etude':
 *   Home | Study Coordinator
 *
 * 'coordinateur_stage':
 *   Home | Internship Coordinator
 *
 * 'chef_departement':
 *   Home | Department Head
 *
 * All other roles ('admin', 'student', null):
 *   A $menus array is built dynamically. Base items: Home, Dashboard,
 *   Destinations, Folders/Profile.
 *   - 'admin' adds a Messages item.
 *   - 'student' adds a Contact item.
 *   Page URLs use a $suffix ('-admin' or '-student') appended to the menu key.
 *   The active state is true when $activeMenu equals the key or key+suffix.
 *   The 'partners' item for students is rendered as a drop-down (.dropdown)
 *   with two sub-links: ?partner=amu and ?partner=iut.
 *
 * @var string|null   $lang        Current language code; falls back to $_SESSION['lang'] or 'fr'
 * @var string|null   $activeMenu  Identifier of the current active nav item for highlighting
 * @var string|null   $userRole    Role of the current user: 'admin' | 'student' | 'coordinateur' | 'coordinateur_etude' | 'coordinateur_stage' | 'chef_departement' | null
 * @var callable|null $t           Translation callable; a fallback closure is created if not injected or not callable
 */

if (empty($userRole) && !empty($_SESSION['role'])) {
    $userRole = $_SESSION['role'];
}

if (!isset($t) || !is_callable($t)) {
    $lang = $lang ?? $_SESSION['lang'] ?? 'fr';
    $t = function(array $frEn) use ($lang): string {
        return $frEn[$lang] ?? $frEn['fr'] ?? '';
    };
}

if (!isset($lang)) {
    $lang = $_SESSION['lang'] ?? 'fr';
}

$isCoordinateur = in_array($userRole, ['coordinateur_etude', 'coordinateur_stage', 'chef_departement', 'coordinateur'], true);

$currentPage = $_GET['page'] ?? (
$userRole === 'admin'        ? 'home-admin'        :
    ($isCoordinateur             ? 'home-coordinateur' : 'home-student')
);
?>
<header>
    <div class="top-bar">
        <img class="logo_amu" src="img/logo.png" alt="Logo AMU">
        <div class="right-buttons">
            <div class="lang-dropdown">
                <button class="dropbtn"><?= htmlspecialchars($lang) ?></button>
                <div class="dropdown-content">
                    <a href="?page=<?= urlencode($currentPage) ?>&lang=fr">Français</a>
                    <a href="?page=<?= urlencode($currentPage) ?>&lang=en">English</a>
                </div>
            </div>

            <?php
            $isHomePage = in_array($activeMenu, ['home', 'home-coordinateur'], true);
            ?>

            <?php if ($isHomePage): ?>
                <?php if (isset($_SESSION['role'])): ?>
                    <button onclick="window.location.href='index.php?page=logout&lang=<?= urlencode($lang) ?>'">
                        <?= $t(['fr' => 'Se déconnecter', 'en' => 'Log out']) ?>
                    </button>
                <?php else: ?>
                    <button onclick="window.location.href='index.php?page=login&lang=<?= urlencode($lang) ?>'">
                        <?= $t(['fr' => 'Se connecter', 'en' => 'Log in']) ?>
                    </button>
                <?php endif; ?>

                <button id="theme-toggle" title="Enable tritanopia accessibility mode">
                    <span class="toggle-switch"></span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <nav class="menu">

        <?php if ($userRole === 'coordinateur') : ?>

            <button
                <?= $activeMenu === 'home-coordinateur' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=home-coordinateur&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => 'Accueil', 'en' => 'Home']) ?>
            </button>
            <button
                <?= $activeMenu === 'coordinateur-etude' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=coordinateur-etude&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => "Coordinateur d'étude", 'en' => 'Study Coordinator']) ?>
            </button>
            <button
                <?= $activeMenu === 'coordinateur-stage' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=coordinateur-stage&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => 'Coordinateur de stage', 'en' => 'Internship Coordinator']) ?>
            </button>

        <?php elseif ($userRole === 'coordinateur_etude') : ?>

            <button
                <?= $activeMenu === 'home-coordinateur' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=home-coordinateur&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => 'Accueil', 'en' => 'Home']) ?>
            </button>
            <button
                <?= $activeMenu === 'coordinateur-etude' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=coordinateur-etude&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => "Coordinateur d'étude", 'en' => 'Study Coordinator']) ?>
            </button>

        <?php elseif ($userRole === 'coordinateur_stage') : ?>

            <button
                <?= $activeMenu === 'home-coordinateur' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=home-coordinateur&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => 'Accueil', 'en' => 'Home']) ?>
            </button>
            <button
                <?= $activeMenu === 'coordinateur-stage' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=coordinateur-stage&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => 'Coordinateur de stage', 'en' => 'Internship Coordinator']) ?>
            </button>

        <?php elseif ($userRole === 'chef_departement') : ?>

            <button
                <?= $activeMenu === 'home-coordinateur' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=home-coordinateur&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => 'Accueil', 'en' => 'Home']) ?>
            </button>
            <button
                <?= $activeMenu === 'chef-departement' ? 'class="active"' : '' ?>
                    onclick="window.location.href='index.php?page=chef-departement&lang=<?= urlencode($lang) ?>'">
                <?= $t(['fr' => "Chef de département", 'en' => 'Department Head']) ?>
            </button>

        <?php else : ?>

            <?php
            $suffix = $userRole === 'admin' ? '-admin' : '-student';

            $menus = [
                'home' => [
                    'fr' => 'Accueil',
                    'en' => 'Home',
                ],
                'dashboard' => [
                    'fr' => ($userRole === 'admin' ? 'Tableau de bord' : 'Mon Tableau de bord'),
                    'en' => ($userRole === 'admin' ? 'Dashboard' : 'My Dashboard'),
                ],
                'partners' => [
                    'fr' => 'Destinations',
                    'en' => 'Destinations',
                ],
                'folders' => [
                    'fr' => ($userRole === 'admin' ? 'Dossiers' : 'Mon Dossier'),
                    'en' => ($userRole === 'admin' ? 'Profiles' : 'My Profile'),
                ],
            ];

            if ($userRole === 'admin') {
                $menus['messages'] = ['fr' => 'Messages', 'en' => 'Messages'];
            }

            if ($userRole === 'student') {
                $menus['contact'] = ['fr' => 'Contact', 'en' => 'Contact'];
            }

            foreach ($menus as $key => $labels):
                $isActive = $activeMenu === $key || $activeMenu === $key . $suffix;                $page     = $key . $suffix;
                $url      = 'index.php?page=' . urlencode($page) . '&lang=' . urlencode($lang);

                if ($key === 'partners' && $userRole === 'student'):
                    $urlAmu = 'index.php?page=partners-student&partner=amu&lang=' . urlencode($lang);
                    $urlIut = 'index.php?page=partners-student&partner=iut&lang=' . urlencode($lang);
                    ?>
                    <div class="dropdown <?= $isActive ? 'active' : '' ?>">
                        <button <?= $isActive ? 'class="active"' : '' ?>>
                            <?= $t($labels) ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="<?= htmlspecialchars($urlAmu) ?>">AMU</a>
                            <a href="<?= htmlspecialchars($urlIut) ?>">IUT</a>
                        </div>
                    </div>
                <?php else : ?>
                    <button
                        <?= $isActive ? 'class="active"' : '' ?>
                            onclick="window.location.href='<?= htmlspecialchars($url) ?>'">
                        <?= $t($labels) ?>
                    </button>
                <?php endif;
            endforeach; ?>

        <?php endif; ?>

    </nav>
</header>