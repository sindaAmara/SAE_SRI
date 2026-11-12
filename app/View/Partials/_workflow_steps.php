<?php
/**
 * Partial: Frise du circuit de validation (5 étapes) + boutons d'action
 *
 * Affiche l'étape où se trouve le dossier (calculée par WorkflowStep::fromRow)
 * et, selon le rôle connecté, le bouton permettant de passer à l'étape suivante
 * ou d'annuler la dernière étape validée. Les boutons postent vers
 * index.php?page=valider_etape (FoldersControllerAdmin::validerEtape) ;
 * l'ordre des étapes et les droits sont revérifiés côté serveur.
 *
 * Utilisable sur la page dossier admin et les pages coordinateur / chef de dépt.
 *
 * @var array<string, mixed> $studentData Ligne du dossier (doit contenir IsComplete, valide_admin, valide_coordinateur, avis_chef_departement)
 * @var string               $PAGE        Page courante, utilisée pour revenir sur la fiche après l'action
 * @var string               $lang        Langue courante
 * @var Closure              $t           Traduction — accepte ['fr' => '...', 'en' => '...']
 */

use Model\Entity\WorkflowStep;
use Model\UseCase\ManageFolderUseCase;

$wfRow     = $studentData;
$wfRole    = strval($_SESSION['role'] ?? '');
$wfStep    = WorkflowStep::fromRow($wfRow);
$wfIndex   = WorkflowStep::index($wfStep);
$wfNumEtu  = strval($wfRow['NumEtu'] ?? '');
$wfAvis    = strval($wfRow['avis_chef_departement'] ?? '');
$wfChefIdx = WorkflowStep::index(WorkflowStep::VALIDE_CHEF);

$wfDate = function (mixed $value): string {
    if (empty($value) || !is_string($value)) return '';
    $ts = strtotime($value);
    return $ts ? date('d/m/Y', $ts) : '';
};

// Informations affichées sous chaque étape
$wfMeta = [
    WorkflowStep::INCOMPLET           => '',
    WorkflowStep::A_VALIDER           => '',
    WorkflowStep::VALIDE_SECRETARIAT  => $wfDate($wfRow['date_validation_admin'] ?? null),
    WorkflowStep::VALIDE_COORDINATEUR => $wfDate($wfRow['date_validation_coordinateur'] ?? null),
    WorkflowStep::VALIDE_CHEF         => '',
];

// L'étape "incomplet" est franchie dès que le dossier est complet : on l'affiche comme "Complet"
$wfLabels = WorkflowStep::LABELS;
if ($wfIndex >= 1) {
    $wfLabels[WorkflowStep::INCOMPLET] = ['fr' => 'Complet', 'en' => 'Complete'];
}

// Bouton "étape suivante" et bouton "annuler" selon l'étape et le rôle
$wfNext = match ($wfStep) {
    WorkflowStep::INCOMPLET          => ['complet',      ['fr' => 'Marquer le dossier complet',          'en' => 'Mark folder as complete']],
    WorkflowStep::A_VALIDER          => ['secretariat',  ['fr' => 'Valider administrativement',          'en' => 'Validate (admin office)']],
    WorkflowStep::VALIDE_SECRETARIAT => ['coordinateur', ['fr' => 'Valider (coordinateur)',              'en' => 'Validate (coordinator)']],
    default                          => null,
};
if ($wfNext !== null && !ManageFolderUseCase::peutAgirSurEtape($wfNext[0], $wfRole)) {
    $wfNext = null;
}

$wfCancel = null;
foreach (['coordinateur', 'secretariat', 'complet'] as $wfAction) {
    $reached = $wfIndex >= WorkflowStep::index(ManageFolderUseCase::ETAPE_ACTIONS[$wfAction]);
    if ($reached && ManageFolderUseCase::peutAgirSurEtape($wfAction, $wfRole)) {
        $wfCancel = [$wfAction, match ($wfAction) {
            'coordinateur' => ['fr' => 'Annuler la validation coordinateur', 'en' => 'Cancel coordinator validation'],
            'secretariat'  => ['fr' => 'Annuler la validation secrétariat',  'en' => 'Cancel admin validation'],
            'complet'      => ['fr' => 'Repasser le dossier en incomplet',    'en' => 'Set folder back to incomplete'],
        }];
        break;
    }
}

$wfAction = 'index.php?page=valider_etape&lang=' . urlencode($lang);
?>
<section class="workflow" aria-labelledby="workflow-title">
    <h2 id="workflow-title" class="workflow__title">
        <?= $t(['fr' => 'Circuit de validation', 'en' => 'Validation workflow']) ?>
    </h2>

    <ol class="workflow__steps">
        <?php foreach (WorkflowStep::ORDER as $i => $code) : ?>
            <?php
            $isChefRefused = $code === WorkflowStep::VALIDE_CHEF && $wfAvis === 'refuse' && $wfIndex >= $wfChefIdx - 1;
            $classes = ['workflow__step', 'workflow__step--' . $code];
            if ($i <= $wfIndex)  $classes[] = 'workflow__step--done';
            if ($i === $wfIndex) $classes[] = 'workflow__step--current';
            if ($isChefRefused)  $classes[] = 'workflow__step--refused';
            ?>
            <li class="<?= implode(' ', $classes) ?>" <?= $i === $wfIndex ? 'aria-current="step"' : '' ?>>
                <span class="workflow__num"><?= $i + 1 ?></span>
                <span class="workflow__label"><?= htmlspecialchars($t($wfLabels[$code])) ?></span>
                <?php if ($isChefRefused) : ?>
                    <span class="workflow__meta"><?= $t(['fr' => 'Refusé par le chef de département', 'en' => 'Refused by dept. head']) ?></span>
                <?php elseif ($i <= $wfIndex && $wfMeta[$code] !== '') : ?>
                    <span class="workflow__meta"><?= $t(['fr' => 'le', 'en' => 'on']) ?> <?= $wfMeta[$code] ?></span>
                <?php elseif ($i === $wfIndex + 1) : ?>
                    <span class="workflow__meta"><?= $t(['fr' => 'Prochaine étape', 'en' => 'Next step']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="workflow__actions">
        <?php if ($wfNext !== null) : ?>
            <form method="post" action="<?= htmlspecialchars($wfAction) ?>">
                <input type="hidden" name="numetu"      value="<?= htmlspecialchars($wfNumEtu) ?>">
                <input type="hidden" name="etape"       value="<?= htmlspecialchars($wfNext[0]) ?>">
                <input type="hidden" name="decision"    value="valider">
                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($PAGE) ?>">
                <button type="submit" class="workflow__btn">✔ <?= $t($wfNext[1]) ?></button>
            </form>
        <?php elseif ($wfIndex === $wfChefIdx - 1 && $wfRole === 'chef_departement') : ?>
            <p class="workflow__hint"><?= $t(['fr' => 'Utilisez les boutons « Accepter / Refuser le dossier » pour donner votre décision.', 'en' => 'Use the "Accept / Refuse" buttons to record your decision.']) ?></p>
        <?php elseif ($wfIndex < $wfChefIdx) : ?>
            <p class="workflow__hint"><?= $t(['fr' => "En attente d'une validation par un autre profil.", 'en' => 'Waiting for validation by another profile.']) ?></p>
        <?php endif; ?>

        <?php if ($wfCancel !== null) : ?>
            <form method="post" action="<?= htmlspecialchars($wfAction) ?>"
                  onsubmit="return confirm(<?= htmlspecialchars(strval(json_encode($t(['fr' => 'Annuler cette étape ? Les étapes suivantes seront aussi remises à zéro.', 'en' => 'Cancel this step? Following steps will be reset too.']))), ENT_QUOTES) ?>);">
                <input type="hidden" name="numetu"      value="<?= htmlspecialchars($wfNumEtu) ?>">
                <input type="hidden" name="etape"       value="<?= htmlspecialchars($wfCancel[0]) ?>">
                <input type="hidden" name="decision"    value="devalider">
                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($PAGE) ?>">
                <button type="submit" class="workflow__btn workflow__btn--secondary">↺ <?= $t($wfCancel[1]) ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($wfAvis === 'accepte' && $wfIndex < $wfChefIdx - 1) : ?>
        <p class="workflow__note">
            ⚠ <?= $t(['fr' => "Un avis favorable du chef de département est enregistré, mais il ne compte qu'une fois les étapes précédentes validées.", 'en' => 'A favourable decision from the department head is recorded, but it only counts once the previous steps are validated.']) ?>
        </p>
    <?php endif; ?>
</section>
