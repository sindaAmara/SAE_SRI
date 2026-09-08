<?php
/**
 * Partial: "Last modified by … on …" banner
 *
 * Displays the name of the administrator who last modified the folder and the
 * date/time of that modification, formatted as DD/MM/YYYY at HH:MM.
 * When both $modifiePar and $modifieLe are empty, a fallback message indicating
 * that the folder has never been modified is shown instead.
 *
 * @var string|null $modifiePar Name of the administrator who made the last modification, or null if unknown
 * @var string|null $modifieLe  Timestamp of the last modification in Y-m-d H:i:s format, or null if never modified
 * @var Closure     $t          Translation callable — accepts ['fr' => '...', 'en' => '...']
 */
?>
<div class="banniere-modifie-par">
    <span class="banniere-modifie-par__icone">✏️</span>
    <span class="banniere-modifie-par__texte">
        <?php if (!empty($modifiePar) || !empty($modifieLe)) : ?>
            <?= $t(['fr' => 'Dernière modification par', 'en' => 'Last modified by']) ?>
            <strong><?= htmlspecialchars($modifiePar ?? '') ?></strong>            <?php if (!empty($modifieLe)) : ?>
                <?= $t(['fr' => 'le', 'en' => 'on']) ?>
                <strong><?= htmlspecialchars(date('d/m/Y', (int) strtotime($modifieLe))) ?></strong>
                <?= $t(['fr' => 'à', 'en' => 'at']) ?>
                <strong><?= htmlspecialchars(date('H:i', (int) strtotime($modifieLe))) ?></strong>
            <?php endif; ?>
        <?php else : ?>
            <?= $t(['fr' => 'Dossier jamais modifié', 'en' => 'Folder never modified']) ?>
        <?php endif; ?>
    </span>
</div>