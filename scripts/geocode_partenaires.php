<?php
/**
 * scripts/geocode_partenaires.php
 *
 * Script CLI a lancer UNE FOIS (puis a nouveau uniquement si de nouveaux
 * partenaires sans coordonnees sont ajoutes) pour remplir latitude/longitude
 * dans la table Partenaires a partir des colonnes pays + ville.
 *
 * Reprend exactement le bootstrap de public/index.php : autoload Composer,
 * Autoloader maison, chargement explicite de la classe globale Database
 * (elle n'est pas namespacee, donc pas couverte par les regles Core\ de
 * l'autoloader), puis chargement du .env.
 *
 * Utilise l'API gratuite Nominatim (OpenStreetMap) ; respecte sa politique
 * d'usage : 1 requete/seconde maximum, User-Agent identifiable obligatoire.
 *
 * Usage (a la racine du projet) :
 *   php scripts/geocode_partenaires.php
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/Autoloader.php';
require_once ROOT_PATH . '/app/Core/Database.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

Autoloader::register();

const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
const USER_AGENT     = 'AMU-RelationsInternationales/1.0 (contact: amarasindouda@gmail.com)';
const RATE_LIMIT_SEC = 1; // Nominatim : 1 req/s maximum

/**
 * @return array{lat: float, lng: float}|null
 */
function geocode(string $ville, string $pays): ?array
{
    $query = http_build_query([
        'q'      => $ville . ', ' . $pays,
        'format' => 'json',
        'limit'  => 1,
    ]);

    $ch = curl_init(NOMINATIM_URL . '?' . $query);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['User-Agent: ' . USER_AGENT],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
        return null;
    }

    return [
        'lat' => (float) $data[0]['lat'],
        'lng' => (float) $data[0]['lon'],
    ];
}

$pdo = Database::getInstance()->getConnection();

$stmt = $pdo->query(
    'SELECT id, pays, ville, universite_institution
     FROM Partenaires
     WHERE latitude IS NULL OR longitude IS NULL'
);
$rows = $stmt->fetchAll();

$total   = count($rows);
$done    = 0;
$skipped = 0;

echo "Geocodage de {$total} partenaire(s) sans coordonnees...\n";

$update = $pdo->prepare(
    'UPDATE Partenaires SET latitude = :lat, longitude = :lng WHERE id = :id'
);

foreach ($rows as $row) {
    // Certaines lignes ont ville et/ou pays a NULL en base : on les compte
    // comme un echec (rien a geocoder) sans planter le script pour autant,
    // et sans consommer inutilement une requete Nominatim.
    if (empty($row['ville']) || empty($row['pays'])) {
        echo "  [ECHEC] #{$row['id']} {$row['universite_institution']} (ville ou pays manquant en base)\n";
        $skipped++;
        continue;
    }

    $coords = geocode((string) $row['ville'], (string) $row['pays']);

    if ($coords === null) {
        echo "  [ECHEC] #{$row['id']} {$row['universite_institution']} ({$row['ville']}, {$row['pays']})\n";
        $skipped++;
    } else {
        $update->execute([
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
            'id'  => $row['id'],
        ]);
        echo "  [OK]    #{$row['id']} {$row['universite_institution']} -> {$coords['lat']}, {$coords['lng']}\n";
        $done++;
    }

    sleep(RATE_LIMIT_SEC);
}

echo "\nTermine : {$done} geocode(s), {$skipped} echec(s) sur {$total}.\n";
if ($skipped > 0) {
    echo "Astuce : relancez le script plus tard pour reessayer les echecs (ville/pays mal orthographies, etc.),\n";
    echo "ou completez manuellement latitude/longitude en base pour ces lignes.\n";
}