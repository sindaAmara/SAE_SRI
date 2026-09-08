<?php
/**
 * View: Student Partner Destinations
 *
 * A read-only page that displays a link to the official partner university list
 * for either the AMU or IUT programme, selected by $partner.
 *
 * ── CONTENT BRANCH ───────────────────────────────────────────────────────────
 * $partner === 'amu':
 *   Shows a translated intro sentence and a link to the AMU partner network
 *   page (https://www.univ-amu.fr/fr/public/universites-et-reseaux-partenaires).
 *
 * $partner !== 'amu' (i.e. 'iut' or any other value):
 *   Shows a translated intro sentence and a link to the IUT study-abroad page
 *   (https://iut.univ-amu.fr/fr/international/partir-etranger#tab-4499).
 *
 * Both links open in a new tab (target="_blank"). No interactive form is
 * rendered for students — this view is entirely static.
 *
 * ── PARTNER MAP IMAGE ────────────────────────────────────────────────────────
 * Identical tritanopia-aware image swap to partners_admin.php:
 * - $_SESSION['tritanopia'] === true → img/University_green.png
 * - otherwise                        → img/University.png
 *
 * A hidden #app-config div carries data-lang and data-role="student".
 *
 * The rendered HTML is passed to the base layout with styles (partners.css),
 * no scripts. $activeMenu = 'partners', $userRole = 'student'.
 * $title is set via htmlspecialchars($titre) (same convention as partners_admin.php).
 *
 * @var string                                 $lang    Current language code (e.g. 'fr' or 'en')
 * @var string                                 $titre   Raw page title string; htmlspecialchars() is applied when setting both the <h1> and the $title layout variable
 * @var string                                 $partner Partner programme selector: 'amu' shows the AMU link; any other value (typically 'iut') shows the IUT link
 * @var Closure(array<string, string>): string $t       Translation callable — accepts ['fr' => '...', 'en' => '...']
 */

ob_start();
?>

    <h1><?= htmlspecialchars($titre) ?></h1>

<?php if ($partner === 'amu'): ?>

    <p>
        <?= $t([
            'fr' => 'Veuillez trouver la liste des destinations d\'AMU en cliquant sur ce lien :',
            'en' => 'Please find the list of AMU\'s destinations by clicking on this link:',
        ]) ?>
    </p>
    <p class="lien">
        <a href="https://www.univ-amu.fr/fr/public/universites-et-reseaux-partenaires" target="_blank">
            Universites-et-reseaux-partenaires
        </a>
    </p>

<?php else: ?>

    <p>
        <?= $t([
            'fr' => 'Veuillez trouver la liste des destinations de l\'IUT en cliquant sur ce lien :',
            'en' => 'Please find the list of IUT\'s destinations by clicking on this link:',
        ]) ?>
    </p>
    <p class="lien">
        <a href="https://iut.univ-amu.fr/fr/international/partir-etranger#tab-4499" target="_blank">
            Partir à l'étranger avec l'IUT
        </a>
    </p>

<?php endif; ?>

    <img id="Université_partenaires"
         src="img/<?= isset($_SESSION['tritanopia']) && $_SESSION['tritanopia'] ? 'University_green.png' : 'University.png' ?>"
         alt="Partner Universities">
    </img>

<div id="app-config"
     data-lang="<?= htmlspecialchars($lang) ?>"
     data-role="student"
     style="display:none;">
</div>
<?php
$content = ob_get_clean();

$title = htmlspecialchars($titre);
$styles = ['styles/partners.css'];
$scripts = [];
$activeMenu = 'partners';
$userRole = 'student';

include __DIR__ . '/../Layout/base.php';