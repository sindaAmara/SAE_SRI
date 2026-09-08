<?php
/**
 * View: Student Home Page
 *
 * Landing page for authenticated (and anonymous) student users. Renders a
 * custom header with a language switcher, a login/logout button depending on
 * $isLoggedIn, and a tritanopia accessibility toggle. The navigation bar links
 * to the student dashboard, partner destination pages (AMU / IUT), the student
 * folder, and the contact page.
 *
 * Below the header, a hero section displays the AMU logo followed by a
 * promotional banner whose image switches to the tritanopia-friendly version
 * when the corresponding session preference is active.
 *
 * The rendered HTML is captured via output buffering into $content and passed
 * to the base_home layout along with the page title, active menu key, styles,
 * scripts, user role, and meta description.
 *
 * @var string                                        $lang        Current language code (e.g. 'fr' or 'en')
 * @var Closure(array<string, string>): string        $t           Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var Closure(string, array<string, mixed>=): string $buildUrl   URL builder callable — accepts a base URL and an optional query-parameter array
 * @var bool                                          $isLoggedIn  Whether the current user is authenticated; controls the login/logout button
 */

$isTritanopia = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);

ob_start();
?>

    <header>
        <div class="top-bar">
            <img class="logo_amu" src="img/logo.png" alt="AMU Logo">

            <div class="right-buttons">
                <div class="lang-dropdown">
                    <button class="dropbtn"><?= htmlspecialchars($lang) ?></button>
                    <div class="dropdown-content">
                        <a href="#" onclick="window.mainApp.changeLang('fr'); return false;">Français</a>
                        <a href="#" onclick="window.mainApp.changeLang('en'); return false;">English</a>
                    </div>
                </div>

                <?php if ($isLoggedIn) : ?>
                    <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'logout']) ?>'">
                        <?= $t(['fr' => 'Se déconnecter','en' => 'Log out']) ?>
                    </button>
                <?php else : ?>
                    <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'login']) ?>'">
                        <?= $t(['fr' => 'Se connecter','en' => 'Log in']) ?>
                    </button>
                <?php endif; ?>

                <button id="theme-toggle" title="Mode tritanopie">
                    <span class="toggle-switch"></span>
                </button>
            </div>
        </div>

        <nav class="menu">
            <button class="active" onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'home-student']) ?>'"><?= $t(['fr' => 'Accueil','en' => 'Home']) ?></button>
            <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'dashboard-student']) ?>'"><?= $t(['fr' => 'Mon Tableau de bord','en' => 'My Dashboard']) ?></button>
            <div class="dropdown">
                <button><?= $t(['fr' => 'Destinations','en' => 'Destinations']) ?></button>
                <div class="dropdown-content">
                    <a href="<?= $buildUrl('index.php', ['page' => 'partners-student', 'partner' => 'amu']) ?>">AMU</a>
                    <a href="<?= $buildUrl('index.php', ['page' => 'partners-student', 'partner' => 'iut']) ?>">IUT</a>
                </div>
            </div>
            <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'folders-student']) ?>'"><?= $t(['fr' => 'Mon Dossier','en' => 'My Profil']) ?></button>
            <button onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'contact-student']) ?>'"><?= $t(['fr' => 'Contact','en' => 'Contact']) ?></button>

        </nav>
    </header>

    <section class="hero-section">
        <img class="hero_logo" src="img/amu.png" alt="AMU Logo Large">
    </section>

    <section class="pub-section">
        <img id="pub_amu"
             src="<?= $isTritanopia ? 'img/etudiants_daltoniens.png' : 'img/image_etudiants.png' ?>"
             alt="Promotion AMU">
        <div class="pub-text">
            <?= $t([
                'fr' => 'Aix-Marseille Université, une université ouverte sur le monde',
                'en' => 'Aix-Marseille University, a university open to the world'
            ]) ?>
        </div>
    </section>

    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="student"
         style="display:none;">
    </div>

<?php
$content = ob_get_clean();

$title = $t([
    'fr' => 'Accueil - Service des relations internationales AMU',
    'en' => 'Home - International Relations Service AMU'
]);
$styles = ['styles/homepage.css'];
$scripts = [];
$activeMenu = 'home';
$userRole = 'student';

$metaDescription = $t([
    'fr' => 'Service des relations internationales de l\'AMU. Informations pour étudiants internationaux, échanges universitaires et partenariats.',
    'en' => 'International Relations Service of AMU. Info for international students, university exchanges, and partnerships.'
]);

include __DIR__ . '/../Layout/base_home.php';