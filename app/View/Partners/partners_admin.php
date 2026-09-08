<?php
/**
 * View: Admin Partner Management
 *
 * Displays the partner universities page for the admin role. Contains two
 * distinct sections: a partner creation form and a static reference panel.
 *
 * ── FEEDBACK MESSAGES ────────────────────────────────────────────────────────
 * Shown below the page heading, mutually exclusive:
 * - $success = true  : a #success-message paragraph is rendered (the JS in
 *   partner.js may use this ID to scroll to or auto-dismiss the notice).
 * - $errorMessage non-empty : an error paragraph is rendered with the escaped
 *   error string.
 * Neither is shown when $success is false and $errorMessage is null / empty.
 *
 * ── PARTNER CREATION FORM ────────────────────────────────────────────────────
 * A .btn-add-partner button toggles the visibility of #partner-form-container
 * (.partner-form.hidden) via partner.js. The cancel button inside the form
 * also hides the panel.
 *
 * The form POSTs to the current URL (action=""). Five required fields:
 * - name        (text)   : continent (e.g. "Europe")
 * - country     (text)   : country name
 * - city        (text)   : city name
 * - institution (text)   : university / institution name
 * - type        (select) : 'amu' or 'iut' — determines which partner list the
 *                          entry belongs to
 *
 * ── STATIC REFERENCE PANEL ───────────────────────────────────────────────────
 * Two external links to the official AMU and IUT partner directories (open in
 * new tab). These are hardcoded URLs, not driven by any injected variable.
 *
 * ── PARTNER MAP IMAGE ────────────────────────────────────────────────────────
 * A static map image (#Université_partenaires) switches between two variants
 * based on the tritanopia session flag:
 * - $_SESSION['tritanopia'] === true → img/University_green.png
 * - otherwise                        → img/University.png
 * This check is performed inline with no PHP variable; it reads the session
 * directly in the template.
 *
 * A hidden #app-config div carries data-lang and data-role="admin" for partner.js.
 *
 * The rendered HTML is passed to the base layout with styles (partners.css)
 * and scripts (partner.js). $activeMenu = 'partners', $userRole = 'admin'.
 * Note: $title is set via htmlspecialchars($titre) — the layout variable is
 * the already-escaped version of the injected $titre string.
 *
 * @var string                                    $lang         Current language code (e.g. 'fr' or 'en')
 * @var string                                    $titre        Raw page title string; htmlspecialchars() is applied when setting both the <h1> and the $title layout variable
 * @var Closure(array<string, string>): string    $t            Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var bool                                      $success      True when a partner was successfully added in the current request
 * @var string|null                               $errorMessage Non-null, non-empty string when partner creation failed; null or empty otherwise
 */

ob_start();
?>

    <h1><?= htmlspecialchars($titre) ?></h1>

<?php if ($success) : ?>
    <p id="success-message" class="success-message">
        <?= $t(['fr' => 'Partenaire ajouté avec succès.', 'en' => 'Partner successfully added.']) ?>
    </p>
<?php elseif (!empty($errorMessage)) : ?>
    <p class="error-message"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

    <div class="partners-actions">
        <button class="btn-add-partner">
            <span class="btn-plus">+</span>
            <?= $t(['fr' => 'Ajouter', 'en' => 'Add']) ?>
        </button>
        <div id="partner-form-container" class="partner-form hidden">
            <form method="post" action="">
                <div class="form-group">
                    <label for="name"><?= $t(['fr' => 'Continent', 'en' => 'Continent']) ?></label>
                    <input type="text" id="name" name="name" required placeholder="<?= $t(['fr' => 'Ex: Europe', 'en' => 'Ex: Europe']) ?>">
                </div>

                <div class="form-group">
                    <label for="country"><?= $t(['fr' => 'Pays', 'en' => 'Country']) ?></label>
                    <input type="text" id="country" name="country" required placeholder="<?= $t(['fr' => 'Ex: France', 'en' => 'Ex: France']) ?>">
                </div>

                <div class="form-group">
                    <label for="city"><?= $t(['fr' => 'Ville', 'en' => 'City']) ?></label>
                    <input type="text" id="city" name="city" required placeholder="<?= $t(['fr' => 'Ex: Marseille', 'en' => 'Ex: Marseille']) ?>">
                </div>

                <div class="form-group">
                    <label for="institution"><?= $t(['fr' => 'Universités et institutions', 'en' => 'Universities and institutions']) ?></label>
                    <input type="text" id="institution" name="institution" required placeholder="<?= $t(['fr' => 'Ex: Aix-Marseille Université', 'en' => 'Ex: Aix-Marseille University']) ?>">
                </div>

                <div class="form-group">
                    <label for="type"><?= $t(['fr' => 'Type', 'en' => 'Type']) ?></label>
                    <select id="type" name="type" required>
                        <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Select --']) ?></option>
                        <option value="amu">AMU</option>
                        <option value="iut">IUT</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save"><?= $t(['fr' => 'Enregistrer', 'en' => 'Save']) ?></button>
                    <button type="button" class="btn-cancel"><?= $t(['fr' => 'Annuler', 'en' => 'Cancel']) ?></button>
                </div>
            </form>
        </div>
    </div>

    <p><?= $t([
            'fr' => 'Veuillez trouver la liste des partenaires d\'AMU et IUT en cliquant sur ces liens :',
            'en' => 'Please find the list of AMU and IUT\'s partners by clicking on these links:'
        ]) ?></p>
    <p class="lien">
        <a href="https://www.univ-amu.fr/fr/public/universites-et-reseaux-partenaires" target="_blank">
            Universites-et-reseaux-partenaires
        </a>
        <br>
        <a href="https://iut.univ-amu.fr/fr/international/partir-etranger#tab-4499" target="_blank">
            Partir à l'étranger avec l'IUT
        </a>
    </p>

    <img id="Université_partenaires"
         src="img/<?= isset($_SESSION['tritanopia']) && $_SESSION['tritanopia'] ? 'University_green.png' : 'University.png' ?>"
         alt="Partner Universities">
    </img>  
    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="admin"
         style="display:none;">
    </div>

<?php
$content = ob_get_clean();

$title = htmlspecialchars($titre);
$styles = ['styles/partners.css'];
$scripts = ['js/partner.js'];
$activeMenu = 'partners';
$userRole = 'admin';

include __DIR__ . '/../Layout/base.php';