<?php
/**
 * View: Student Folder (create / update)
 *
 * Dual-mode view that serves both the initial folder creation form and the
 * update form for an existing folder. The mode is determined by $isCreateMode
 * (true when $dossier is empty). Key behavioural differences between modes:
 *
 * CREATE MODE ($isCreateMode = true):
 * - Required fields (last name, first name, personal email, phone, type, zone)
 *   are active and marked with *.
 * - Name, gender, component, department, AMU email, type, zone, and mobility
 *   type are editable.
 * - Administration-only fields (campus, study level, averages, DRI advice,
 *   start date, previous mobility) are disabled with 'input-admin' styling.
 * - Mobility type select is active (used to show/hide convention vs. motivation
 *   letter upload blocks via JavaScript).
 * - Form posts to create_folder.
 *
 * UPDATE MODE ($isCreateMode = false):
 * - Administrative fields remain disabled. Personal-contact fields (address,
 *   postal code, city, personal email, phone, discipline, degree program,
 *   country) stay editable so the student can keep their details current.
 * - Locked fields (name, gender, AMU email, component, department, type,
 *   zone, mobility type) are readonly/disabled to prevent unilateral changes.
 * - Form posts to update_my_folder.
 *
 * DEADLINE BANNER:
 * When $dossier contains a DateLimite in update mode, a colour-coded banner
 * is shown above the form:
 * - Blue/info  : deadline is more than 7 days away.
 * - Orange/warn: deadline is within 7 days.
 * - Red/locked : deadline has passed — file upload inputs are replaced by a
 *   locked message ($uploadBloque = true) and no new documents can be submitted.
 *
 * DOCUMENTS SECTION:
 * Renders individual upload blocks for five document types (photo, CV,
 * convention, motivation letter, language certificate). Convention and
 * motivation letter blocks are hidden by default and toggled by JavaScript
 * based on the mobility type. Each block shows the current validation status
 * badge, a download link when a file exists, any admin comment, and a file
 * upload input (or the locked message when $uploadBloque is true).
 *
 * The rendered HTML is captured via output buffering into $content and passed
 * to the base layout with styles (folders.css, chatbot.css), scripts
 * (chatbot.js, folders.js), active menu key 'folders', and role 'student'.
 *
 * @var array<string, mixed> $dossier    Raw folder data from the repository (PascalCase keys); empty array in create mode
 * @var string               $studentId Student number, displayed as a readonly field and embedded in hidden inputs
 * @var string               $message   Optional feedback message to display at the top of the view (may be empty)
 * @var string               $lang      Current language code (e.g. 'fr' or 'en')
 */

if (!isset($t)) {
    $t = function(array $translations) use ($lang) {
        return $translations[$lang] ?? $translations['fr'] ?? '';
    };
}

$buildUrl = function(string $path, array $params = []) use ($lang): string {
    $params['lang'] = $lang;
    $separator = (strpos($path, '?') === false) ? '?' : '&';
    return $path . $separator . http_build_query($params);
};

$isCreateMode = empty($dossier);
$formAction   = $isCreateMode ? 'create_folder' : 'update_my_folder';
$rawPieces    = $dossier['pieces'] ?? [];
$pieces       = is_array($rawPieces) ? $rawPieces : [];

$detectedType = '';
if (!$isCreateMode) {
    if (!empty($pieces['convention']['file'])) {
        $detectedType = 'stage';
    } elseif (!empty($pieces['lettre_motivation']['file'])) {
        $detectedType = 'etudes';
    }
}

$valNom               = htmlspecialchars(strval($dossier['Nom'] ?? ''));
$valPrenom            = htmlspecialchars(strval($dossier['Prenom'] ?? ''));
$valDate              = htmlspecialchars(strval($dossier['DateNaissance'] ?? ''));
$valSexe              = strval($dossier['Sexe'] ?? '');
$valEmailP            = htmlspecialchars(strval($dossier['EmailPersonnel'] ?? ''));
$valEmailA            = htmlspecialchars(strval($dossier['EmailAMU'] ?? ''));
$valTel               = htmlspecialchars(strval($dossier['Telephone'] ?? ''));
$valAdresse           = htmlspecialchars(strval($dossier['Adresse'] ?? ''));
$valCP                = htmlspecialchars(strval($dossier['CodePostal'] ?? ''));
$valVille             = htmlspecialchars(strval($dossier['Ville'] ?? ''));
$valDept              = htmlspecialchars(strval($dossier['CodeDepartement'] ?? ''));
$valComposante        = htmlspecialchars(strval($dossier['Composante'] ?? ''));
$valCampus            = htmlspecialchars(strval($dossier['Campus'] ?? ''));
$valDiscipline        = htmlspecialchars(strval($dossier['Discipline'] ?? ''));
$valNiveauEtude       = htmlspecialchars(strval($dossier['NiveauEtude'] ?? ''));
$valFormation         = htmlspecialchars(strval($dossier['Formation'] ?? ''));
$valMoyenneBac        = htmlspecialchars(strval($dossier['MoyenneBac'] ?? ''));
$valMoyenneSansBac    = htmlspecialchars(strval($dossier['MoyenneSansBac'] ?? ''));
$valAvisDRI           = htmlspecialchars(strval($dossier['AvisDRI'] ?? ''));
$valDateDebut         = htmlspecialchars(strval($dossier['DateDebut'] ?? ''));
$valMobiliteAnterieure= htmlspecialchars(strval($dossier['MobiliteAnterieure'] ?? ''));
$valPays              = htmlspecialchars(strval($dossier['Pays'] ?? ''));
$valType              = strval($dossier['Type'] ?? '');
$valZone              = strval($dossier['Zone'] ?? '');

$clsReadonly  = $isCreateMode ? '' : 'input-readonly';
$clsDisabled  = 'input-admin';

ob_start();
?>

    <h1><?= $t(['fr' => 'Mon dossier étudiant', 'en' => 'My Student Profile']) ?></h1>

<?php if (!empty($message)) : ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php
$dateLimitRaw  = !$isCreateMode ? ($dossier['DateLimite'] ?? null) : null;
$dateLimite    = is_string($dateLimitRaw) && $dateLimitRaw !== '' ? $dateLimitRaw : null;
$uploadBloque  = false;

if ($dateLimite !== null) :
    $dateObj    = \DateTime::createFromFormat('Y-m-d', $dateLimite);
    $aujourdhui = new \DateTime('today');
    $diff       = $dateObj ? (int) $aujourdhui->diff($dateObj)->days : null;
    $estDepasse = $dateObj && $dateObj < $aujourdhui;
    $estProche  = $dateObj && !$estDepasse && $diff !== null && $diff <= 7;

    if ($estDepasse) {
        $modifierClass = 'banniere-date-limite--depasse';
        $icone         = '🔒';
        $labelDate     = $t(['fr' => 'Date limite dépassée',        'en' => 'Deadline passed']);
        $sousTitre     = $t(['fr' => 'Le dépôt de pièces n\'est plus possible.', 'en' => 'Document submission is no longer possible.']);
    } elseif ($estProche) {
        $modifierClass = 'banniere-date-limite--proche';
        $icone         = '⚠️';
        $labelDate     = $t(['fr' => 'Date limite proche',          'en' => 'Deadline approaching']);
        $sousTitre     = $t(['fr' => "Plus que $diff jour(s) pour déposer vos pièces.", 'en' => "Only $diff day(s) left to submit your documents."]);
    } else {
        $modifierClass = 'banniere-date-limite--info';
        $icone         = '📅';
        $labelDate     = $t(['fr' => 'Date limite de dépôt',        'en' => 'Submission deadline']);
        $sousTitre     = $t(['fr' => 'Pensez à déposer vos pièces avant cette date.', 'en' => 'Remember to submit your documents before this date.']);
    }

    $dateFormatee = $dateObj
        ? ($lang === 'en' ? $dateObj->format('F j, Y') : $dateObj->format('d/m/Y'))
        : $dateLimite;
    ?>
    <div class="banniere-date-limite <?= $modifierClass ?>">
        <span class="banniere-date-limite__icone"><?= $icone ?></span>
        <div class="banniere-date-limite__body">
            <p class="banniere-date-limite__label"><?= $labelDate ?></p>
            <p class="banniere-date-limite__date"><?= htmlspecialchars($dateFormatee) ?></p>
            <p class="banniere-date-limite__sous-titre"><?= $sousTitre ?></p>
        </div>
    </div>
    <?php
    $uploadBloque = !$isCreateMode && $estDepasse;
endif;
?>

    <form method="post"
          action="<?= $buildUrl('index.php', ['page' => $formAction]) ?>"
          enctype="multipart/form-data"
          class="creation-form">

        <div class="form-section">

            <label><?= $t(['fr' => 'Numéro étudiant', 'en' => 'Student ID']) ?></label>
            <input type="text" value="<?= htmlspecialchars($studentId) ?>" readonly class="input-admin">
            <input type="hidden" name="numetu" value="<?= htmlspecialchars($studentId) ?>">

            <label><?= $t(['fr' => 'Nom *', 'en' => 'Last Name *']) ?></label>
            <input type="text" name="nom" value="<?= $valNom ?>"
                <?= $isCreateMode ? 'required' : 'readonly' ?>
                   class="<?= $clsReadonly ?>">

            <label><?= $t(['fr' => 'Prénom *', 'en' => 'First Name *']) ?></label>
            <input type="text" name="prenom" value="<?= $valPrenom ?>"
                <?= $isCreateMode ? 'required' : 'readonly' ?>
                   class="<?= $clsReadonly ?>">

            <label><?= $t(['fr' => 'Date de naissance', 'en' => 'Date of Birth']) ?></label>
            <input type="date" name="naissance" value="<?= $valDate ?>"
                <?= $isCreateMode ? '' : 'readonly' ?>
                   class="<?= $clsReadonly ?>">

            <label><?= $t(['fr' => 'Sexe', 'en' => 'Gender']) ?></label>
            <select name="sexe" id="sexe"
                <?= $isCreateMode ? '' : 'disabled' ?>
                    class="<?= $clsReadonly ?>">
                <option value="M"    <?= $valSexe === 'M'    ? 'selected' : '' ?>><?= $t(['fr' => 'Masculin', 'en' => 'Male']) ?></option>
                <option value="F"    <?= $valSexe === 'F'    ? 'selected' : '' ?>><?= $t(['fr' => 'Féminin',  'en' => 'Female']) ?></option>
                <option value="Autre"<?= $valSexe === 'Autre'? 'selected' : '' ?>><?= $t(['fr' => 'Autre',    'en' => 'Other']) ?></option>
            </select>

            <label><?= $t(['fr' => 'Adresse', 'en' => 'Address']) ?></label>
            <input type="text" name="adresse" value="<?= $valAdresse ?>">

            <label><?= $t(['fr' => 'Code postal', 'en' => 'Postal Code']) ?></label>
            <input type="text" name="cp" value="<?= $valCP ?>">

            <label><?= $t(['fr' => 'Ville', 'en' => 'City']) ?></label>
            <input type="text" name="ville" value="<?= $valVille ?>">

            <label><?= $t(['fr' => 'Email personnel *', 'en' => 'Personal Email *']) ?></label>
            <input type="email" name="email_perso" value="<?= $valEmailP ?>" required>

            <label><?= $t(['fr' => 'Email AMU', 'en' => 'AMU Email']) ?></label>
            <input type="email" name="email_amu" value="<?= $valEmailA ?>"
                <?= $isCreateMode ? '' : 'readonly' ?>
                   class="<?= $clsReadonly ?>">

            <label><?= $t(['fr' => 'Téléphone *', 'en' => 'Phone *']) ?></label>
            <input type="text" name="telephone" value="<?= $valTel ?>" required>

            <label><?= $t(['fr' => 'Composante', 'en' => 'Component']) ?></label>
            <input type="text" name="composante" value="<?= $valComposante ?>"
                <?= $isCreateMode ? '' : 'readonly' ?>
                   class="<?= $clsReadonly ?>">

            <label><?= $t(['fr' => 'Code Département', 'en' => 'Department Code']) ?></label>
            <input type="text" name="departement" value="<?= $valDept ?>"
                <?= $isCreateMode ? '' : 'readonly' ?>
                   class="<?= $clsReadonly ?>">

            <label><?= $t(['fr' => 'Discipline', 'en' => 'Discipline']) ?></label>
            <input type="text" name="discipline" value="<?= $valDiscipline ?>">

            <label><?= $t(['fr' => 'Formation', 'en' => 'Degree Program']) ?></label>
            <input type="text" name="formation" value="<?= $valFormation ?>">

            <label><?= $t(['fr' => 'Pays', 'en' => 'Country']) ?></label>
            <input type="text" name="pays" value="<?= $valPays ?>">

            <label><?= $t(['fr' => 'Campus', 'en' => 'Campus']) ?></label>
            <input type="text" name="campus" value="<?= $valCampus ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label><?= $t(['fr' => 'Niveau d\'étude', 'en' => 'Study Level']) ?></label>
            <input type="text" name="niveau_etude" value="<?= $valNiveauEtude ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label><?= $t(['fr' => 'Moyenne Bac', 'en' => 'High School Average']) ?></label>
            <input type="text" name="moyenne_bac" value="<?= $valMoyenneBac ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label><?= $t(['fr' => 'Moyenne sans Bac', 'en' => 'Average w/o High School']) ?></label>
            <input type="text" name="moyenne_sans_bac" value="<?= $valMoyenneSansBac ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label><?= $t(['fr' => 'Avis DRI', 'en' => 'DRI Advice']) ?></label>
            <input type="text" name="avis_dri" value="<?= $valAvisDRI ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label><?= $t(['fr' => 'Date de début', 'en' => 'Start Date']) ?></label>
            <input type="text" name="date_debut" value="<?= $valDateDebut ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label><?= $t(['fr' => 'A déjà effectué une mobilité', 'en' => 'Previous Mobility']) ?></label>
            <input type="text" name="mobilite_anterieure" value="<?= $valMobiliteAnterieure ?>" disabled class="<?= $clsDisabled ?>"
                   title="<?= $t(['fr' => 'Réservé à l\'administration', 'en' => 'Administration only']) ?>">

            <label for="type"><?= $t(['fr' => 'Type *', 'en' => 'Type *']) ?></label>
            <select name="type" id="type"
                <?= $isCreateMode ? 'required' : 'disabled' ?>
                    class="<?= $clsReadonly ?>">
                <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Choose --']) ?></option>
                <option value="entrant" <?= $valType === 'entrant' ? 'selected' : '' ?>><?= $t(['fr' => 'Entrant', 'en' => 'Incoming']) ?></option>
                <option value="sortant" <?= $valType === 'sortant' ? 'selected' : '' ?>><?= $t(['fr' => 'Sortant', 'en' => 'Outgoing']) ?></option>
            </select>

            <label for="zone"><?= $t(['fr' => 'Zone *', 'en' => 'Zone *']) ?></label>
            <select name="zone" id="zone"
                <?= $isCreateMode ? 'required' : 'disabled' ?>
                    class="<?= $clsReadonly ?>">
                <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Choose --']) ?></option>
                <option value="europe"     <?= $valZone === 'europe'     ? 'selected' : '' ?>><?= $t(['fr' => 'Europe',      'en' => 'Europe']) ?></option>
                <option value="hors_europe"<?= $valZone === 'hors_europe'? 'selected' : '' ?>><?= $t(['fr' => 'Hors Europe', 'en' => 'Non-Europe']) ?></option>
            </select>

            <label for="mobilite_type"><?= $t(['fr' => 'Type de mobilité', 'en' => 'Mobility Type']) ?></label>
            <select name="mobilite_type" id="mobilite_type"
                <?= $isCreateMode ? '' : 'disabled' ?>
                    class="<?= $clsReadonly ?>">
                <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Choose --']) ?></option>
                <option value="stage" <?= $detectedType === 'stage'  ? 'selected' : '' ?>><?= $t(['fr' => 'Stage',  'en' => 'Internship']) ?></option>
                <option value="etudes"<?= $detectedType === 'etudes' ? 'selected' : '' ?>><?= $t(['fr' => 'Études', 'en' => 'Studies']) ?></option>
            </select>

        </div><!-- /.form-section -->

        <div class="form-section form-section--pieces">
            <h2 class="form-section__titre"><?= $t(['fr' => 'Mes pièces justificatives', 'en' => 'My Documents']) ?></h2>

            <?php
            $docTypes = [
                'photo'            => ['label' => $t(['fr' => 'Photo',                    'en' => 'Photo']),               'id' => 'doc_photo',              'name' => 'photo',             'accept' => 'image/*'],
                'cv'               => ['label' => $t(['fr' => 'CV',                        'en' => 'CV']),                  'id' => 'doc_cv',                 'name' => 'cv',                'accept' => '.pdf,.doc,.docx'],
                'convention'       => ['label' => $t(['fr' => 'Convention de stage',       'en' => 'Internship Agreement']),'id' => 'justificatif_convention','name' => 'convention',        'accept' => '.pdf,.doc,.docx'],
                'lettre_motivation'=> ['label' => $t(['fr' => 'Lettre de motivation',      'en' => 'Motivation Letter']),   'id' => 'lettre_motivation',      'name' => 'lettre_motivation', 'accept' => '.pdf,.doc,.docx'],
                'langues'          => ['label' => $t(['fr' => 'Attestation de langues',    'en' => 'Language Certificate']),'id' => 'doc_langues',            'name' => 'langues_file',      'accept' => '.pdf,.doc,.docx,.jpg,.png'],
            ];

            $statusText = [
                'pending'  => $t(['fr' => 'En attente', 'en' => 'Pending']),
                'accepted' => $t(['fr' => 'Accepté',    'en' => 'Accepted']),
                'refused'  => $t(['fr' => 'Refusé',     'en' => 'Refused']),
            ];

            foreach ($docTypes as $key => $info) :
                $doc     = $pieces[$key] ?? null;
                $hasFile = !empty($doc['file']);
                $status  = is_array($doc) ? strval($doc['status'] ?? 'pending') : 'pending';
                $comment = is_array($doc) ? strval($doc['comment'] ?? '') : '';

                $hidden = ($key === 'convention' || $key === 'lettre_motivation') ? 'piece-block--hidden' : '';
                ?>

                <div id="<?= $info['id'] ?>" class="piece-block <?= $hidden ?>">

                    <div class="piece-block__header">
                        <label class="piece-block__label"><?= $info['label'] ?></label>
                        <?php if ($hasFile) : ?>
                            <span class="piece-block__badge piece-block__badge--<?= $status ?>">
                                <?= $statusText[$status] ?? $status ?>
                            </span>
                        <?php else : ?>
                            <span class="piece-block__non-fourni"><?= $t(['fr' => 'Non fourni', 'en' => 'Not provided']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasFile && is_array($doc)) : ?>
                        <div class="piece-block__download">
                            <a href="data:application/octet-stream;base64,<?= strval($doc['file']) ?>"
                               download="<?= $key ?>_<?= htmlspecialchars($studentId) ?>"
                               class="btn-secondary btn-download-piece">
                                📥 <?= $t(['fr' => 'Télécharger mon fichier actuel', 'en' => 'Download my current file']) ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($comment)) : ?>
                        <div class="piece-block__comment">
                            <strong><?= $t(['fr' => 'Commentaire de l\'administration :', 'en' => 'Administration comment:']) ?></strong>
                            <span><?= nl2br(htmlspecialchars($comment)) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($uploadBloque) : ?>
                        <p class="piece-block__bloque">
                            🔒 <?= $t(['fr' => 'La date limite est dépassée, vous ne pouvez plus déposer de fichiers.', 'en' => 'The deadline has passed, you can no longer submit files.']) ?>
                        </p>
                    <?php else : ?>
                        <div class="piece-block__upload">
                            <label class="piece-block__upload-label">
                                <?= $hasFile
                                    ? $t(['fr' => 'Remplacer ce fichier :', 'en' => 'Replace this file:'])
                                    : $t(['fr' => 'Ajouter un fichier :',   'en' => 'Add a file:']) ?>
                            </label>
                            <input type="file" name="<?= $info['name'] ?>" accept="<?= $info['accept'] ?>" class="piece-block__file-input">
                        </div>
                    <?php endif; ?>

                </div><!-- /.piece-block -->

            <?php endforeach; ?>
        </div><!-- /.form-section--pieces -->

        <div class="form-actions form-actions--centered">
            <?php if ($isCreateMode) : ?>
                <button type="submit" class="btn-primary btn-submit">
                    <?= $t(['fr' => 'Déposer ma demande', 'en' => 'Submit my application']) ?>
                </button>
            <?php else : ?>
                <button type="submit" class="btn-secondary btn-submit">
                    <?= $t(['fr' => 'Enregistrer mes modifications', 'en' => 'Save changes']) ?>
                </button>
            <?php endif; ?>

            <button type="button" class="btn-secondary"
                    onclick="window.location.href='<?= $buildUrl('index.php', ['page' => 'home-student']) ?>'">
                <?= $t(['fr' => 'Annuler', 'en' => 'Cancel']) ?>
            </button>
        </div>

    </form>

    <div id="app-config"
         data-lang="<?= htmlspecialchars($lang) ?>"
         data-role="student"
         style="display:none;">
    </div>
<?php
$content = ob_get_clean();

$title = $t(['fr' => 'Mon profil - Étudiant', 'en' => 'My Profile - Student']);

$styles    = ['styles/folders.css', 'styles/chatbot.css'];
$scripts   = ['js/chatbot.js', 'js/folders.js'];
$activeMenu = 'folders';
$userRole   = 'student';

include __DIR__ . '/../Layout/base.php';