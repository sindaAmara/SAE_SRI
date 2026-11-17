<?php
/**
 * Recalcule « complet / incomplet » pour tous les dossiers, à partir de leurs pièces.
 *
 * À lancer UNE FOIS pour corriger les anciens dossiers (marqués complets avant que le calcul
 * soit automatique). Ensuite, chaque enregistrement de pièce garde la valeur à jour tout seul.
 *
 * Règle : complet = toutes les pièces obligatoires fournies ET acceptées.
 * Un dossier qui repasse incomplet perd ses validations (secrétariat, coordinateur, chef).
 *
 * Utilisation (depuis la racine du projet) :
 *   php cron/recalcul_complet.php --simulation   → affiche ce qui changerait, ne modifie rien
 *   php cron/recalcul_complet.php                → applique les changements
 *
 * La base utilisée est celle du fichier .env.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Autoloader.php';
require_once __DIR__ . '/../app/Core/Database.php';
Autoloader::register();

use Model\Persistence\FolderRepositoryPDO;
use Model\UseCase\ManageFolderUseCase;

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$simulation  = in_array('--simulation', $argv, true) || in_array('--dry-run', $argv, true);
$useCase     = new ManageFolderUseCase(new FolderRepositoryPDO());
$changements = $useCase->synchroniserTous($simulation);

echo $simulation ? "=== SIMULATION : rien n'est modifié ===\n" : "=== Changements appliqués ===\n";

if ($changements === []) {
    echo "Aucun dossier à corriger : tout est déjà à jour.\n";
    exit(0);
}

foreach ($changements as $c) {
    printf(
        "%-12s %-30s %-9s -> %-9s (pièces acceptées : %s)%s\n",
        $c['numEtu'],
        mb_strimwidth($c['nom'], 0, 30),
        $c['avant'],
        $c['apres'],
        $c['pieces'],
        $c['validationsAnnulees'] ? '  ⚠ validations annulées' : ''
    );
}

echo "\n" . count($changements) . " dossier(s) " . ($simulation ? 'seraient corrigés.' : 'corrigés.') . "\n";
if ($simulation) {
    echo "Pour appliquer : php cron/recalcul_complet.php\n";
}
