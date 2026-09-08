<?php
/**
 * Partial: Student folder form fields
 *
 * Renders all editable fields for a student folder. Each field can be in one
 * of three states depending on the combination of $allEditable and $editableFields:
 *
 * - Fully editable ($allEditable = true): all inputs are active (admin edit mode).
 * - Selectively editable ($editableFields non-empty): only the listed field names
 *   receive the 'input-editable' class; all others are readonly.
 * - All readonly (default / view mode): every input carries readonly and
 *   'input-disabled'; used when the admin is viewing without editing.
 *
 * Because HTML <select> elements submit no value when disabled, every disabled
 * select is paired with a hidden mirror input (controlled by the $needsHidden
 * closure) so that existing values are preserved on POST.
 *
 * The partial emits two hidden inputs (numetu, redirect_to) that must be
 * present in the surrounding <form> for POST submissions to work correctly.
 *
 * @var array<string, mixed> $studentData     Associative array of raw folder data from the repository (PascalCase keys)
 * @var string               $numEtu          Already htmlspecialchars-encoded student number
 * @var string               $detectedType    Detected mobility type used to pre-select the mobilite_type dropdown: 'stage' | 'etude' | ''
 * @var string               $redirectPage    Value for the hidden redirect_to field; controls where the form posts back to
 * @var array<string>|null   $editableFields  List of field names that should be editable when $allEditable is false; empty array means all fields are readonly
 * @var bool|null            $allEditable     When true, all fields are active (admin full-edit mode); defaults to false
 * @var Closure              $t               Translation callable — accepts ['fr' => '...', 'en' => '...']
 */

$activeFields = $editableFields ?? [];
$isEditable   = $allEditable    ?? false;

/**
 * Returns the readonly attribute and CSS class for an input field.
 *
 * readonly keeps the value in the POST payload (unlike disabled).
 * If $isEditable is true, no attributes are added (field is fully active).
 * Otherwise, the field is editable only if its name appears in $activeFields.
 */
$fieldAttrs = function(string $name) use ($activeFields, $isEditable): string {
    if ($isEditable) {
        return '';
    }
    if (in_array($name, $activeFields, true)) {
        return 'class="input-editable"';
    }
    return 'readonly class="input-disabled"';
};

/**
 * Returns the disabled attribute and CSS class for a select element.
 *
 * disabled prevents the value from being submitted, so every disabled select
 * must be paired with a hidden mirror input (see $needsHidden).
 * Returns the appropriate attributes based on editability of the field.
 */
$selectAttrs = function(string $name) use ($activeFields, $isEditable): string {
    if ($isEditable) {
        return '';
    }
    if (in_array($name, $activeFields, true)) {
        return 'class="input-editable"';
    }
    return 'disabled class="input-disabled"';
};

/**
 * Returns true when a select field needs a hidden mirror input.
 *
 * A mirror input is needed when the select is disabled (and therefore excluded
 * from the POST payload) so that the existing database value is preserved.
 */
$needsHidden = function(string $name) use ($activeFields, $isEditable): bool {
    if ($isEditable) return false;
    if (in_array($name, $activeFields, true)) return false;
    return true;
};

$labelClass = function(string $name) use ($activeFields, $isEditable): string {
    if (!$isEditable && in_array($name, $activeFields, true)) {
        return ' class="label-editable"';
    }
    return '';
};
?>

    <input type="hidden" name="numetu"      value="<?= $numEtu ?>">
    <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectPage) ?>">

    <label><?= $t(['fr' => 'NumÉtu', 'en' => 'Student ID']) ?></label>
    <input type="text" value="<?= $numEtu ?>" disabled class="input-disabled">

    <label<?= $labelClass('nom') ?>><?= $t(['fr' => 'Nom', 'en' => 'Last Name']) ?></label>
    <input type="text" name="nom" value="<?= htmlspecialchars(strval($studentData['Nom'] ?? '')) ?>" <?= $fieldAttrs('nom') ?>>

    <label<?= $labelClass('prenom') ?>><?= $t(['fr' => 'Prénom', 'en' => 'First Name']) ?></label>
    <input type="text" name="prenom" value="<?= htmlspecialchars(strval($studentData['Prenom'] ?? '')) ?>" <?= $fieldAttrs('prenom') ?>>

    <label<?= $labelClass('naissance') ?>><?= $t(['fr' => 'Né(e) le', 'en' => 'Date of Birth']) ?></label>
    <input type="date" name="naissance" value="<?= htmlspecialchars(strval($studentData['DateNaissance'] ?? '')) ?>" <?= $fieldAttrs('naissance') ?>>

    <label<?= $labelClass('sexe') ?>><?= $t(['fr' => 'Sexe', 'en' => 'Gender']) ?></label>
    <select name="sexe" <?= $selectAttrs('sexe') ?>>
        <option value="M"    <?= ($studentData['Sexe'] ?? '') === 'M'     ? 'selected' : '' ?>><?= $t(['fr' => 'Masculin', 'en' => 'Male'])   ?></option>
        <option value="F"    <?= ($studentData['Sexe'] ?? '') === 'F'     ? 'selected' : '' ?>><?= $t(['fr' => 'Féminin',  'en' => 'Female']) ?></option>
        <option value="Autre"<?= ($studentData['Sexe'] ?? '') === 'Autre' ? 'selected' : '' ?>><?= $t(['fr' => 'Autre',    'en' => 'Other'])  ?></option>
    </select>
<?php if ($needsHidden('sexe')) : ?>
    <input type="hidden" name="sexe" value="<?= htmlspecialchars(strval($studentData['Sexe'] ?? '')) ?>">
<?php endif; ?>

    <label<?= $labelClass('adresse') ?>><?= $t(['fr' => 'Adresse', 'en' => 'Address']) ?></label>
    <input type="text" name="adresse" value="<?= htmlspecialchars(strval($studentData['Adresse'] ?? '')) ?>" <?= $fieldAttrs('adresse') ?>>

    <label<?= $labelClass('cp') ?>><?= $t(['fr' => 'Code postal', 'en' => 'Postal Code']) ?></label>
    <input type="text" name="cp" value="<?= htmlspecialchars(strval($studentData['CodePostal'] ?? '')) ?>" <?= $fieldAttrs('cp') ?>>

    <label<?= $labelClass('ville') ?>><?= $t(['fr' => 'Ville', 'en' => 'City']) ?></label>
    <input type="text" name="ville" value="<?= htmlspecialchars(strval($studentData['Ville'] ?? '')) ?>" <?= $fieldAttrs('ville') ?>>

    <label<?= $labelClass('email_perso') ?>><?= $t(['fr' => 'Email Personnel', 'en' => 'Personal Email']) ?></label>
    <input type="email" name="email_perso" value="<?= htmlspecialchars(strval($studentData['EmailPersonnel'] ?? '')) ?>" <?= $fieldAttrs('email_perso') ?>>

    <label<?= $labelClass('email_amu') ?>><?= $t(['fr' => 'Email AMU', 'en' => 'AMU Email']) ?></label>
    <input type="email" name="email_amu" value="<?= htmlspecialchars(strval($studentData['EmailAMU'] ?? '')) ?>" <?= $fieldAttrs('email_amu') ?>>

    <label<?= $labelClass('telephone') ?>><?= $t(['fr' => 'Téléphone', 'en' => 'Phone']) ?></label>
    <input type="text" name="telephone" value="<?= htmlspecialchars(strval($studentData['Telephone'] ?? '')) ?>" <?= $fieldAttrs('telephone') ?>>

    <label<?= $labelClass('composante') ?>><?= $t(['fr' => 'Composante', 'en' => 'Component']) ?></label>
    <input type="text" name="composante" value="<?= htmlspecialchars(strval($studentData['Composante'] ?? '')) ?>" <?= $fieldAttrs('composante') ?>>

    <label<?= $labelClass('departement') ?>><?= $t(['fr' => 'Département', 'en' => 'Department']) ?></label>
    <input type="text" name="departement" value="<?= htmlspecialchars(strval($studentData['CodeDepartement'] ?? '')) ?>" <?= $fieldAttrs('departement') ?>>

    <label<?= $labelClass('campus') ?>><?= $t(['fr' => 'Campus', 'en' => 'Campus']) ?></label>
    <input type="text" name="campus" value="<?= htmlspecialchars(strval($studentData['Campus'] ?? '')) ?>" <?= $fieldAttrs('campus') ?>>

    <label<?= $labelClass('discipline') ?>><?= $t(['fr' => 'Discipline', 'en' => 'Discipline']) ?></label>
    <input type="text" name="discipline" value="<?= htmlspecialchars(strval($studentData['Discipline'] ?? '')) ?>" <?= $fieldAttrs('discipline') ?>>

    <label<?= $labelClass('niveau_etude') ?>><?= $t(['fr' => 'Niveau d\'étude', 'en' => 'Study Level']) ?></label>
    <input type="text" name="niveau_etude" value="<?= htmlspecialchars(strval($studentData['NiveauEtude'] ?? '')) ?>" <?= $fieldAttrs('niveau_etude') ?>>

    <label<?= $labelClass('formation') ?>><?= $t(['fr' => 'Formation', 'en' => 'Degree Program']) ?></label>
    <input type="text" name="formation" value="<?= htmlspecialchars(strval($studentData['Formation'] ?? '')) ?>" <?= $fieldAttrs('formation') ?>>

    <label<?= $labelClass('moyenne_bac') ?>><?= $t(['fr' => 'Moyenne Bac', 'en' => 'High School Average']) ?></label>
    <input type="text" name="moyenne_bac" value="<?= htmlspecialchars(strval($studentData['MoyenneBac'] ?? '')) ?>" <?= $fieldAttrs('moyenne_bac') ?>>

    <label<?= $labelClass('moyenne_sans_bac') ?>><?= $t(['fr' => 'Moyenne sans Bac', 'en' => 'Average w/o High School']) ?></label>
    <input type="text" name="moyenne_sans_bac" value="<?= htmlspecialchars(strval($studentData['MoyenneSansBac'] ?? '')) ?>" <?= $fieldAttrs('moyenne_sans_bac') ?>>

    <label<?= $labelClass('avis_dri') ?>><?= $t(['fr' => 'Avis DRI', 'en' => 'DRI Advice']) ?></label>
    <input type="text" name="avis_dri" value="<?= htmlspecialchars(strval($studentData['AvisDRI'] ?? '')) ?>" <?= $fieldAttrs('avis_dri') ?>>

    <label<?= $labelClass('date_debut') ?>><?= $t(['fr' => 'Date de début', 'en' => 'Start Date']) ?></label>
    <input type="text" name="date_debut" value="<?= htmlspecialchars(strval($studentData['DateDebut'] ?? '')) ?>" <?= $fieldAttrs('date_debut') ?>>

    <label<?= $labelClass('mobilite_anterieure') ?>><?= $t(['fr' => 'A déjà effectué une mobilité', 'en' => 'Previous Mobility']) ?></label>
    <input type="text" name="mobilite_anterieure" value="<?= htmlspecialchars(strval($studentData['MobiliteAnterieure'] ?? '')) ?>" <?= $fieldAttrs('mobilite_anterieure') ?>>

    <label<?= $labelClass('pays') ?>><?= $t(['fr' => 'Pays', 'en' => 'Country']) ?></label>
    <input type="text" name="pays" value="<?= htmlspecialchars(strval($studentData['Pays'] ?? '')) ?>" <?= $fieldAttrs('pays') ?>>

    <label<?= $labelClass('type') ?>><?= $t(['fr' => 'Type', 'en' => 'Type']) ?></label>
    <select name="type" <?= $selectAttrs('type') ?>>
        <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Choose --']) ?></option>
        <option value="entrant" <?= ($studentData['Type'] ?? '') === 'entrant' ? 'selected' : '' ?>><?= $t(['fr' => 'Entrant', 'en' => 'Incoming']) ?></option>
        <option value="sortant" <?= ($studentData['Type'] ?? '') === 'sortant' ? 'selected' : '' ?>><?= $t(['fr' => 'Sortant', 'en' => 'Outgoing']) ?></option>
    </select>
<?php if ($needsHidden('type')) : ?>
    <input type="hidden" name="type" value="<?= htmlspecialchars(strval($studentData['Type'] ?? '')) ?>">
<?php endif; ?>

    <label<?= $labelClass('zone') ?>><?= $t(['fr' => 'Zone', 'en' => 'Zone']) ?></label>
    <select name="zone" <?= $selectAttrs('zone') ?>>
        <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Choose --']) ?></option>
        <option value="europe"      <?= ($studentData['Zone'] ?? '') === 'europe'      ? 'selected' : '' ?>><?= $t(['fr' => 'Europe',      'en' => 'Europe'])     ?></option>
        <option value="hors_europe" <?= ($studentData['Zone'] ?? '') === 'hors_europe' ? 'selected' : '' ?>><?= $t(['fr' => 'Hors Europe', 'en' => 'Non-Europe']) ?></option>
    </select>
<?php if ($needsHidden('zone')) : ?>
    <input type="hidden" name="zone" value="<?= htmlspecialchars(strval($studentData['Zone'] ?? '')) ?>">
<?php endif; ?>

    <label<?= $labelClass('mobilite_type') ?>><?= $t(['fr' => 'Type de mobilité', 'en' => 'Mobility Type']) ?></label>
    <select name="mobilite_type" <?= $selectAttrs('mobilite_type') ?>>
        <option value=""><?= $t(['fr' => '-- Choisir --', 'en' => '-- Choose --']) ?></option>
        <option value="stage"  <?= $detectedType === 'stage'  ? 'selected' : '' ?>><?= $t(['fr' => 'Stage',  'en' => 'Internship']) ?></option>
        <option value="etude" <?= $detectedType === 'etude' ? 'selected' : '' ?>><?= $t(['fr' => 'Études', 'en' => 'Studies'])    ?></option>
    </select>
<?php if ($needsHidden('mobilite_type')) : ?>
    <input type="hidden" name="mobilite_type" id="hidden_mobilite_type" value="<?= htmlspecialchars($detectedType) ?>">
<?php endif; ?>