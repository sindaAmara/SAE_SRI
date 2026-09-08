<?php
/**
 * View: Admin Site Map
 *
 * Renders a simple unordered list of all pages accessible to the admin role,
 * with each item as a translated, URL-built anchor.
 *
 * ── LINK LIST ────────────────────────────────────────────────────────────────
 * $links is an indexed array of records, each with two keys:
 * - 'url'   : the raw URL string (e.g. 'index.php?page=home-admin') passed
 *             to $buildUrl() which appends the current lang parameter.
 * - 'label' : the French label string, used as the 'fr' key for $t().
 *             The English translation is obtained by passing the same label
 *             string through $translateLabel(), which the controller provides
 *             as a lookup closure mapping French labels to their English
 *             equivalents. The result is then passed as the 'en' key to $t().
 *             Both the resolved URL and translated label are escaped with
 *             htmlspecialchars() before output.
 *
 * A hidden #app-config div carries data-lang and data-role="admin".
 *
 * The rendered HTML is passed to base_minimal.php (logo-only header, standard
 * <main> wrapper) with styles (web_plan.css), no scripts.
 * $activeMenu = 'web_plan', $userRole = 'admin'.
 *
 * This view is structurally identical to web_plan_student.php; the only
 * differences are data-role="admin" and $userRole = 'admin'.
 *
 * @var string                                            $lang           Current language code (e.g. 'fr' or 'en')
 * @var Closure(array<string, string>): string            $t              Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var Closure(string, array<string, mixed>=): string    $buildUrl       URL builder callable — appends lang parameter to the given URL
 * @var array<int, array{url: string, label: string}>     $links          Ordered list of site map entries; each entry has a raw URL and a French label string
 * @var Closure(string): string                           $translateLabel Label translation closure — accepts a French label string and returns its English equivalent
 */

ob_start();
?>

    <h1><?= $t(['fr' => 'Plan du site', 'en' => 'Site Map']) ?></h1>

    <ul>
        <?php foreach ($links as $link) :
            $url = strval($link['url']);
            $label = strval($link['label']);
            ?>
            <li>
                <a href="<?= htmlspecialchars($buildUrl($url)) ?>">
                    <?= htmlspecialchars($t([
                        'fr' => $label,
                        'en' => $translateLabel($label)
                    ])) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="admin"
         style="display:none;">
    </div>

<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Plan du site', 'en' => 'Site Map']);
$styles = ['styles/web_plan.css'];
$scripts = [];
$activeMenu = 'web_plan';
$userRole = 'admin';

include __DIR__ . '/../Layout/base_minimal.php';