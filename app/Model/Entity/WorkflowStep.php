<?php

namespace Model\Entity;

/**
 * WorkflowStep
 *
 * Source unique de vérité pour les 5 étapes du circuit de validation d'un dossier.
 *
 * Chaque dossier se trouve dans UNE SEULE étape, calculée à partir des colonnes
 * existantes de la table `dossiers` (aucune nouvelle colonne) :
 *
 *   1. incomplet           IsComplete = 0 (pièces manquantes ou pas encore toutes acceptées)
 *   2. a_valider           toutes les pièces acceptées (automatique), en attente du secrétariat
 *   3. valide_secretariat  valide_admin = 1, en attente du coordinateur
 *   4. valide_coordinateur valide_coordinateur = 1, en attente du chef de département
 *   5. valide_chef         avis_chef_departement = 'accepte'
 *
 * Les étapes sont cumulatives : une étape n'est atteinte que si toutes les
 * précédentes le sont (ex. un avis "accepte" du chef sans validation secrétariat
 * laisse le dossier à l'étape 2).
 *
 * Le même calcul existe en SQL (sqlCase) et en PHP (fromRow) : les stats de
 * l'accueil, le filtre de la liste et la frise du dossier donnent donc toujours
 * le même résultat.
 */
final class WorkflowStep
{
    public const INCOMPLET           = 'incomplet';
    public const A_VALIDER           = 'a_valider';
    public const VALIDE_SECRETARIAT  = 'valide_secretariat';
    public const VALIDE_COORDINATEUR = 'valide_coordinateur';
    public const VALIDE_CHEF         = 'valide_chef';

    /** @var array<int, string> Étapes dans l'ordre du circuit */
    public const ORDER = [
        self::INCOMPLET,
        self::A_VALIDER,
        self::VALIDE_SECRETARIAT,
        self::VALIDE_COORDINATEUR,
        self::VALIDE_CHEF,
    ];

    /** @var array<string, array{fr: string, en: string}> Libellés affichés */
    public const LABELS = [
        self::INCOMPLET           => ['fr' => 'Incomplet',                 'en' => 'Incomplete'],
        self::A_VALIDER           => ['fr' => 'Pièces conformes, à valider', 'en' => 'Documents OK, awaiting admin'],
        self::VALIDE_SECRETARIAT  => ['fr' => 'Validé secrétariat',        'en' => 'Validated by admin'],
        self::VALIDE_COORDINATEUR => ['fr' => 'Validé coordinateur',       'en' => 'Validated by coordinator'],
        self::VALIDE_CHEF         => ['fr' => 'Validé chef de département', 'en' => 'Validated by dept. head'],
    ];

    /**
     * Expression SQL qui renvoie le code d'étape d'une ligne de `dossiers`.
     *
     * L'ordre des WHEN est important : la première condition vraie l'emporte,
     * ce qui garantit qu'un dossier n'est compté que dans une seule étape.
     */
    public static function sqlCase(): string
    {
        return "(CASE
            WHEN COALESCE(IsComplete, 0) = 0          THEN '" . self::INCOMPLET . "'
            WHEN COALESCE(valide_admin, 0) = 0        THEN '" . self::A_VALIDER . "'
            WHEN COALESCE(valide_coordinateur, 0) = 0 THEN '" . self::VALIDE_SECRETARIAT . "'
            WHEN avis_chef_departement = 'accepte'    THEN '" . self::VALIDE_CHEF . "'
            ELSE '" . self::VALIDE_COORDINATEUR . "'
        END)";
    }

    /**
     * Même calcul que sqlCase(), en PHP, à partir d'une ligne déjà chargée.
     *
     * @param array<string, mixed> $row Ligne de la table `dossiers`
     */
    public static function fromRow(array $row): string
    {
        if (empty($row['IsComplete']))          return self::INCOMPLET;
        if (empty($row['valide_admin']))        return self::A_VALIDER;
        if (empty($row['valide_coordinateur'])) return self::VALIDE_SECRETARIAT;
        if (($row['avis_chef_departement'] ?? null) === 'accepte') return self::VALIDE_CHEF;
        return self::VALIDE_COORDINATEUR;
    }

    /** Position de l'étape dans le circuit (0 à 4), -1 si code inconnu. */
    public static function index(string $step): int
    {
        $i = array_search($step, self::ORDER, true);
        return $i === false ? -1 : (int) $i;
    }

    /** Vrai si le code correspond à une étape connue (utilisé pour valider les filtres). */
    public static function isValid(string $step): bool
    {
        return self::index($step) !== -1;
    }
}
