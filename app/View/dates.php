<?php
/**
 * View: Dates Butoirs
 *
 * @var string                           $lang          Current language code (e.g. 'fr' or 'en')
 * @var array<string, mixed>|null        $dates
 * @var string                           $message       Optional feedback message displayed at the top of either mode (may be empty)
 */

if (!isset($t)) {
    $t = function (array $translations) use ($lang) {
        return $translations[$lang] ?? $translations['fr'] ?? '';
    };
}

$buildUrl = function (string $path, array $params = []) use ($lang): string {
    $params['lang'] = $lang;
    $separator = (strpos($path, '?') === false) ? '?' : '&';
    return $path . $separator . http_build_query($params);
};

$PAGE = 'dates-admin';

$hasActiveFilters = (strval($filters['type']       ?? 'all')) !== 'all'
    || (strval($filters['zone']       ?? 'all')) !== 'all'
    || (strval($filters['complet']    ?? 'all')) !== 'all'
    || (strval($filters['composante'] ?? 'all')) !== 'all'
    || (strval($filters['accord']     ?? 'all')) !== 'all'
    || !empty($filters['date_debut'])
    || !empty($filters['date_fin'])
    || !empty($filters['search']);

ob_start();
?>

            <?php if (!empty($message)) : ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

        <?php
        $selectedDay = $_GET['date'] ?? '';
        $showDatesTable = ($_GET['action'] ?? '') === 'view' && $selectedDay !== '';
        ?>

        <div class="datepicker-container">
        <input type="text" class="datepicker" name="date_limite"
               value="<?= htmlspecialchars($selectedDay) ?>"
               min="<?= date('Y-m-d') ?>"
               style="width:auto;">
        </div>

        <?php if ($showDatesTable) : ?>
        <div class="dates-div">
            <div class="conteneur-dates">
                <table class="table-dates">
                    <thead>
                    <tr>
                        <th><?= $t(['fr' => 'ID', 'en' => 'ID']) ?></th>
                        <th><?= $t(['fr' => 'Evenement', 'en' => 'Event']) ?></th>
                        <th><?= $t(['fr' => 'Description', 'en' => 'Description']) ?></th>
                        <th><?= $t(['fr' => 'Type', 'en' => 'Type']) ?></th>
                        <th><?= $t(['fr' => 'Modifier / Créer', 'en' => 'Modify / Add']) ?></th>
                        <th><?= $t(['fr' => 'Supprimer', 'en' => 'Delete']) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                            <?php
                            $createFormId = 'create-date-form';
                            ?>
                            <tr class="data-row">
                                <td>
                                    <input form="<?= htmlspecialchars($createFormId) ?>"
                                           type="text"
                                           name="id"
                                           required>
                                </td>
                                <td>
                                    <input form="<?= htmlspecialchars($createFormId) ?>"
                                           type="text"
                                           name="titre"
                                           required>
                                </td>
                                <td>
                                    <input form="<?= htmlspecialchars($createFormId) ?>"
                                           type="text"
                                           name="description"
                                           required>
                                </td>
                                <td>
                                    <input form="<?= htmlspecialchars($createFormId) ?>"
                                           type="text"
                                           name="type"
                                           required>
                                    <input form="<?= htmlspecialchars($createFormId) ?>"
                                           type="hidden"
                                           name="date"
                                           value="<?= htmlspecialchars($selectedDay) ?>">
                                </td>
                                <td>
                                    <form id="<?= htmlspecialchars($createFormId) ?>"
                                          method="post"
                                          action="index.php?page=dates-admin&action=add&lang=<?= urlencode($lang) ?>">
                                        <button type="submit">CREER</button>
                                    </form>
                                </td>
                                <td>X</td>
                            </tr>

                    <?php if ($dates !== []) : ?>
                            <?php foreach ($dates as $date) : ?>
                                <?php
                                $eID          = strval($date->getId() ?? '');
                                $eEvent       = strval($date->getTitre() ?? '');
                                $eDescription = strval($date->getDescription() ?? '');
                                $eType        = strval($date->getType() ?? '');
                                $eDate        = strval($date->getDate() ?? '');
                                $updateFormId = 'update-date-form-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $eID);
                                $deleteFormId = 'delete-date-form-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $eID);
                                ?>
                            <tr class="data-row"
                                data-id="<?= htmlspecialchars($eID) ?>">
                                <td>
                                    <?= htmlspecialchars($eID) ?>
                                    <input form="<?= htmlspecialchars($updateFormId) ?>"
                                           type="hidden"
                                           name="id"
                                           value="<?= htmlspecialchars($eID) ?>">
                                </td>
                                <td>
                                    <input form="<?= htmlspecialchars($updateFormId) ?>"
                                           type="text"
                                           name="titre"
                                           value="<?= htmlspecialchars($eEvent) ?>"
                                           required>
                                </td>
                                <td>
                                    <input form="<?= htmlspecialchars($updateFormId) ?>"
                                           type="text"
                                           name="description"
                                           value="<?= htmlspecialchars($eDescription) ?>"
                                           required>
                                </td>
                                <td>
                                    <input form="<?= htmlspecialchars($updateFormId) ?>"
                                           type="text"
                                           name="type"
                                           value="<?= htmlspecialchars($eType) ?>"
                                           required>
                                    <input form="<?= htmlspecialchars($updateFormId) ?>"
                                           type="hidden"
                                           name="date"
                                           value="<?= htmlspecialchars($eDate) ?>">
                                </td>
                                <td>
                                    <form id="<?= htmlspecialchars($updateFormId) ?>"
                                          method="post"
                                          action="index.php?page=dates-admin&action=put&lang=<?= urlencode($lang) ?>">
                                        <button type="submit">MODIFIER</button>
                                    </form>
                                </td>
                                <td>
                                    <form id="<?= htmlspecialchars($deleteFormId) ?>"
                                          method="post"
                                          action="index.php?page=dates-admin&action=delete&lang=<?= urlencode($lang) ?>"
                                          onsubmit="return confirm('Supprimer cette date ?');">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($eID) ?>">
                                        <button type="submit">SUPPRIMER</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination accordion-pagination"></div>
            </div>
        </div>
        <?php else : ?>
            <p class="dates-empty-state">
                <?= $t([
                    'fr' => 'Sélectionnez une date pour gérer les dates importantes.',
                    'en' => 'Select a date to manage important dates.',
                ]) ?>
            </p>
        <?php endif; ?>

        <div id="app-config"
             data-lang="<?= htmlspecialchars($lang) ?>"
             data-role="admin"
             style="display:none;">
        </div>
<?php
$content = ob_get_clean();

$title      = $t(['fr' => 'Gestion des dossiers - Admin', 'en' => 'Profiles Management - Admin']);
$styles     = ['styles/index.css', 'styles/chatbot.css', "styles/flatpickr.min.css", "styles/dates.css"];
$scripts    = ['js/flatpickr.min.js', 'js/dates.js'];
$activeMenu = $PAGE;
$userRole   = 'admin';

include __DIR__ . '/Layout/base.php';