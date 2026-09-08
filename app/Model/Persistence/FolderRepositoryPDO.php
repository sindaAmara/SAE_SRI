<?php
namespace Model\Persistence;

use Model\Repository\FolderRepositoryInterface;
use PDO;
use Database;
use Model\Entity\FolderStats;
use Model\Entity\GenderStats;

/**
 * Class FolderRepositoryPDO
 * * Manages operations for student folders (dossiers), statistics, and administrative workflows.
 */
class FolderRepositoryPDO implements FolderRepositoryInterface
{
    /** @var PDO Database connection */
    private PDO $db;

    /**
     * FolderRepositoryPDO constructor.
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Validates mobility type.
     * * @param string|null $mobilite
     * @return string|null 'etude', 'stage' or null.
     */
    private function validMobilite(?string $mobilite): ?string
    {
        return in_array($mobilite, ['etude', 'stage'], true) ? $mobilite : null;
    }

    /**
     * Generates SQL WHERE clause and bindings for mobility.
     * * @param string|null $mobilite
     * @param bool $hasExistingWhere Whether a WHERE clause already exists.
     * @return array{clause: string, bindings: array<string, string>}
     */
    private function mobiliteWhere(?string $mobilite, bool $hasExistingWhere = false): array
    {
        $mobilite = $this->validMobilite($mobilite);
        if ($mobilite === null) {
            return ['clause' => '', 'bindings' => []];
        }
        $keyword = $hasExistingWhere ? 'AND' : 'WHERE';
        return [
            'clause'   => "$keyword Mobilite = :mobilite",
            'bindings' => ['mobilite' => $mobilite],
        ];
    }

    /**
     * Generates SQL WHERE clause and bindings for department.
     * * @param string|null $departement
     * @param bool $hasExistingWhere Whether a WHERE clause already exists.
     * @return array{clause: string, bindings: array<string, string>}
     */
    private function departementWhere(?string $departement, bool $hasExistingWhere = false): array
    {
        if (empty($departement)) {
            return ['clause' => '', 'bindings' => []];
        }
        $keyword = $hasExistingWhere ? 'AND' : 'WHERE';
        return [
            'clause'   => "$keyword CodeDepartement = :departement",
            'bindings' => ['departement' => $departement],
        ];
    }

    /**
     * Helper to run a query with or without parameters.
     * * @param string $sql
     * @param array<string, mixed> $bindings
     * @return \PDOStatement|false
     */
    private function run(string $sql, array $bindings = []): \PDOStatement|false
    {
        if (empty($bindings)) {
            return $this->db->query($sql);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    // ===========================================================
    // STATS
    // ===========================================================

    /**
     * Proxy for getDossierStats.
     * @return FolderStats
     */
    public function getGlobalStats(): FolderStats
    {
        return $this->getDossierStats();
    }

    /**
     * Calculates folder counts (total vs completed) with optional filters.
     * * @param string|null $mobilite
     * @param string|null $departement
     * @return FolderStats
     */
    public function getDossierStats(?string $mobilite = null, ?string $departement = null): FolderStats
    {
        try {
            ['clause' => $where,  'bindings' => $bindings]  = $this->mobiliteWhere($mobilite);
            ['clause' => $where2, 'bindings' => $bindings2] = $this->departementWhere($departement, !empty($where));
            $where    .= $where2;
            $bindings  = array_merge($bindings, $bindings2);
            $stmt = $this->run("
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN IsComplete = 1 THEN 1 ELSE 0 END) AS completed
                FROM dossiers
                $where
            ", $bindings);
            $result    = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            $total     = (is_array($result) && isset($result['total'])     && is_numeric($result['total']))     ? (int) $result['total']     : 0;
            $completed = (is_array($result) && isset($result['completed']) && is_numeric($result['completed'])) ? (int) $result['completed'] : 0;
            return new FolderStats($total, $completed);
        } catch (\PDOException $e) {
            return new FolderStats(0, 0);
        }
    }

    /**
     * Calculates gender-based statistics.
     * * @param string|null $mobilite
     * @param string|null $departement
     * @return GenderStats
     */
    public function getGenderStats(?string $mobilite = null, ?string $departement = null): GenderStats
    {
        try {
            $mobiliteValid  = $this->validMobilite($mobilite);
            $mobiliteClause = $mobiliteValid       ? "AND Mobilite          = :mobilite"    : '';
            $deptClause     = !empty($departement) ? "AND CodeDepartement   = :departement" : '';
            $bindings       = array_filter([
                'mobilite'    => $mobiliteValid       ?: null,
                'departement' => !empty($departement) ? $departement : null,
            ]);
            $stmt = $this->run("
                SELECT
                    SUM(CASE WHEN LOWER(Sexe) IN ('m','homme','male','masculin')             THEN 1 ELSE 0 END) AS male,
                    SUM(CASE WHEN LOWER(Sexe) IN ('f','femme','female','féminin','feminin')  THEN 1 ELSE 0 END) AS female
                FROM dossiers
                WHERE Sexe IS NOT NULL AND Sexe != ''
                $mobiliteClause
                $deptClause
            ", $bindings);
            $result = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            $male   = (is_array($result) && isset($result['male'])   && is_numeric($result['male']))   ? (int) $result['male']   : 0;
            $female = (is_array($result) && isset($result['female']) && is_numeric($result['female'])) ? (int) $result['female'] : 0;
            return new GenderStats($male, $female);
        } catch (\PDOException $e) {
            error_log("getGenderStats Error: " . $e->getMessage());
            return new GenderStats(0, 0);
        }
    }

    /**
     * Gets the most frequent destination countries excluding France.
     * * @param int $limit Max results.
     * @param string|null $mobilite
     * @param string|null $departement
     * @return array<int, array{name: string, count: int}>
     */
    public function getTopCountries(int $limit, ?string $mobilite = null, ?string $departement = null): array
    {
        try {
            $mobiliteValid  = $this->validMobilite($mobilite);
            $mobiliteClause = $mobiliteValid       ? "AND Mobilite          = :mobilite"    : '';
            $deptClause     = !empty($departement) ? "AND CodeDepartement   = :departement" : '';
            $bindings       = array_filter([
                'mobilite'    => $mobiliteValid       ?: null,
                'departement' => !empty($departement) ? $departement : null,
            ]);
            $stmt = $this->db->prepare("
                SELECT Pays AS name, COUNT(*) AS count
                FROM dossiers
                WHERE Pays IS NOT NULL 
                  AND Pays != '' 
                  AND LOWER(Pays) != 'france'
                $mobiliteClause
                $deptClause
                GROUP BY Pays
                ORDER BY count DESC
                LIMIT :limit
            ");
            foreach ($bindings as $key => $value) { $stmt->bindValue(":$key", $value); }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("getTopCountries Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gets statistics per department.
     * * @param int $limit Max results.
     * @param string|null $mobilite
     * @param string|null $departement
     * @return array<int, array{name: string, count: int}>
     */
    public function getDepartmentStats(int $limit, ?string $mobilite = null, ?string $departement = null): array
    {
        try {
            $mobiliteValid  = $this->validMobilite($mobilite);
            $mobiliteClause = $mobiliteValid       ? "AND Mobilite          = :mobilite"    : '';
            $deptClause     = !empty($departement) ? "AND CodeDepartement   = :departement" : '';
            $bindings       = array_filter([
                'mobilite'    => $mobiliteValid       ?: null,
                'departement' => !empty($departement) ? $departement : null,
            ]);
            $stmt = $this->db->prepare("
                SELECT CodeDepartement AS name, COUNT(*) AS count
                FROM dossiers
                WHERE CodeDepartement IS NOT NULL AND CodeDepartement != ''
                $mobiliteClause
                $deptClause
                GROUP BY CodeDepartement
                ORDER BY count DESC
                LIMIT :limit
            ");
            foreach ($bindings as $key => $value) { $stmt->bindValue(":$key", $value); }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("getDepartmentStats Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compares incoming vs outgoing student counts.
     * * @param string|null $mobilite
     * @param string|null $departement
     * @return array{incoming: int, outgoing: int}
     */
    public function getIncomingOutgoingStats(?string $mobilite = null, ?string $departement = null): array
    {
        try {
            ['clause' => $where,  'bindings' => $bindings]  = $this->mobiliteWhere($mobilite);
            ['clause' => $where2, 'bindings' => $bindings2] = $this->departementWhere($departement, !empty($where));
            $where    .= $where2;
            $bindings  = array_merge($bindings, $bindings2);
            $stmt = $this->run("
                SELECT
                    SUM(CASE WHEN LOWER(Type) = 'entrant' THEN 1 ELSE 0 END) AS incoming,
                    SUM(CASE WHEN LOWER(Type) = 'sortant' THEN 1 ELSE 0 END) AS outgoing
                FROM dossiers
                $where
            ", $bindings);
            if ($stmt === false) return ['incoming' => 0, 'outgoing' => 0];
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($result)) return ['incoming' => 0, 'outgoing' => 0];
            return [
                'incoming' => isset($result['incoming']) && is_numeric($result['incoming']) ? (int) $result['incoming'] : 0,
                'outgoing' => isset($result['outgoing']) && is_numeric($result['outgoing']) ? (int) $result['outgoing'] : 0,
            ];
        } catch (\PDOException $e) {
            error_log("getIncomingOutgoingStats Error: " . $e->getMessage());
            return ['incoming' => 0, 'outgoing' => 0];
        }
    }

    /**
     * Gets statistics per continent.
     * * @param string|null $mobilite
     * @param string|null $departement
     * @return array<int, array{name: string, count: int}>
     */
    public function getContinentStats(?string $mobilite = null, ?string $departement = null): array
    {
        try {
            $mobiliteValid  = $this->validMobilite($mobilite);
            $mobiliteClause = $mobiliteValid       ? "AND Mobilite          = :mobilite"    : '';
            $deptClause     = !empty($departement) ? "AND CodeDepartement   = :departement" : '';
            $bindings       = array_filter([
                'mobilite'    => $mobiliteValid       ?: null,
                'departement' => !empty($departement) ? $departement : null,
            ]);
            $stmt = $this->run("
                SELECT Continent AS name, COUNT(*) AS count
                FROM dossiers
                WHERE Continent IS NOT NULL AND Continent != ''
                $mobiliteClause
                $deptClause
                GROUP BY Continent
                ORDER BY count DESC
            ", $bindings);
            if ($stmt === false) return [];
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(fn($r) => [
                'name'  => isset($r['name'])  && is_string($r['name'])   ? $r['name']        : '',
                'count' => isset($r['count']) && is_numeric($r['count']) ? (int) $r['count'] : 0,
            ], $results);
        } catch (\PDOException $e) {
            error_log("getContinentStats Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compares number of distinct countries in Europe vs rest of the world.
     * * @param string|null $mobilite
     * @param string|null $departement
     * @return array{europe_countries: int, non_europe_countries: int}
     */
    public function getEuropeVsNonEuropeStats(?string $mobilite = null, ?string $departement = null): array
    {
        try {
            $mobiliteValid  = $this->validMobilite($mobilite);
            $mobiliteClause = $mobiliteValid       ? "AND Mobilite          = :mobilite"    : '';
            $deptClause     = !empty($departement) ? "AND CodeDepartement   = :departement" : '';
            $bindings       = array_filter([
                'mobilite'    => $mobiliteValid       ?: null,
                'departement' => !empty($departement) ? $departement : null,
            ]);
            $stmt = $this->run("
                SELECT
                    COUNT(DISTINCT CASE WHEN LOWER(Zone) = 'europe'      THEN Pays END) AS europe_countries,
                    COUNT(DISTINCT CASE WHEN LOWER(Zone) = 'hors_europe' THEN Pays END) AS non_europe_countries
                FROM dossiers
                WHERE Pays IS NOT NULL AND Pays != ''
                $mobiliteClause
                $deptClause
            ", $bindings);
            if ($stmt === false) return ['europe_countries' => 0, 'non_europe_countries' => 0];
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($result)) return ['europe_countries' => 0, 'non_europe_countries' => 0];
            return [
                'europe_countries'     => isset($result['europe_countries'])     && is_numeric($result['europe_countries'])     ? (int) $result['europe_countries']     : 0,
                'non_europe_countries' => isset($result['non_europe_countries']) && is_numeric($result['non_europe_countries']) ? (int) $result['non_europe_countries'] : 0,
            ];
        } catch (\PDOException $e) {
            error_log("getEuropeVsNonEuropeStats Error: " . $e->getMessage());
            return ['europe_countries' => 0, 'non_europe_countries' => 0];
        }
    }

    /**
     * Gets folder counts per geographic zone.
     * * @param string|null $mobilite
     * @param string|null $departement
     * @return array<int, array{name: string, count: int}>
     */
    public function getZoneStats(?string $mobilite = null, ?string $departement = null): array
    {
        try {
            $mobiliteValid  = $this->validMobilite($mobilite);
            $mobiliteClause = $mobiliteValid       ? "AND Mobilite          = :mobilite"    : '';
            $deptClause     = !empty($departement) ? "AND CodeDepartement   = :departement" : '';
            $bindings       = array_filter([
                'mobilite'    => $mobiliteValid       ?: null,
                'departement' => !empty($departement) ? $departement : null,
            ]);
            $stmt = $this->run("
                SELECT Zone AS name, COUNT(*) AS count
                FROM dossiers
                WHERE Zone IS NOT NULL AND Zone != ''
                $mobiliteClause
                $deptClause
                GROUP BY Zone
                ORDER BY count DESC
            ", $bindings);
            if ($stmt === false) return [];
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(fn($r) => [
                'name'  => isset($r['name'])  && is_string($r['name'])   ? $r['name']        : '',
                'count' => isset($r['count']) && is_numeric($r['count']) ? (int) $r['count'] : 0,
            ], $results);
        } catch (\PDOException $e) {
            error_log("getZoneStats Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Infers a continent based on country name and zone.
     * * @param string $pays Country name.
     * @param string $zone Zone (e.g., 'europe').
     * @return string|null Continent name.
     */
    public function inferContinent(string $pays, string $zone): ?string
    {
        if (strtolower($zone) === 'europe') return 'Europe';
        $mapping = [
            'Amérique'     => ['Canada', 'États-Unis', 'Mexique', 'Brésil', 'Argentine', 'Colombie', 'Chili', 'Pérou', 'Venezuela', 'Cuba'],
            'Asie'         => ['Japon', 'Chine', 'Corée du Sud', 'Inde', 'Thaïlande', 'Vietnam', 'Indonésie', 'Singapour', 'Malaisie', 'Philippines', 'Bangladesh', 'Pakistan'],
            'Océanie'      => ['Australie', 'Nouvelle-Zélande', 'Fidji', 'Papouasie-Nouvelle-Guinée'],
            'Afrique'      => ['Maroc', 'Tunisie', 'Algérie', 'Sénégal', "Côte d'Ivoire", 'Cameroun', 'Mali', 'Guinée', 'Madagascar', 'Mozambique', 'Tanzanie', 'Kenya', 'Ghana', 'Nigeria', 'Éthiopie'],
            'Moyen-Orient' => ['Turquie', 'Liban', 'Jordanie', 'Égypte', 'Arabie Saoudite', 'Émirats Arabes Unis', 'Qatar', 'Koweït', 'Israël', 'Iran', 'Irak'],
        ];
        foreach ($mapping as $continent => $countries) {
            if (in_array($pays, $countries, true)) return $continent;
        }
        return null;
    }

    // ===========================================================
    // CRUD
    // ===========================================================

    /**
     * Retrieves all dossiers.
     * * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT NumEtu, Nom, Prenom, EmailPersonnel as email, Telephone, Type, Zone,
                       DateNaissance, Sexe, Adresse, CodePostal, Ville, EmailAMU, CodeDepartement, Composante,
                       Pays, Destination,
                       Campus, Discipline, NiveauEtude, Formation, MoyenneBac, MoyenneSansBac, AvisDRI,
                       DateDebut, MobiliteAnterieure, IsComplete, PiecesJustificatives, status
                FROM dossiers ORDER BY Nom, Prenom
            ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Finds a specific dossier by student number.
     * * @param string $numEtu Student number.
     * @return array<string, mixed>|null Dossier data or null.
     */
    public function findByNumEtu(string $numEtu): ?array
    {
        try {
            $stmt = $this->db->prepare("
            SELECT NumEtu, Nom, Prenom, DateNaissance, Sexe, Adresse, CodePostal, Ville,
                   EmailPersonnel, EmailAMU, Telephone, CodeDepartement, Composante, Type, Zone, Pays,
                   Campus, Discipline, NiveauEtude, Formation, MoyenneBac, MoyenneSansBac, AvisDRI,
                   DateDebut, MobiliteAnterieure, IsComplete, PiecesJustificatives, status,
                   StatutDocuments, DateLimite, CommentaireAdmin, ModifiePar, ModifieLe,
                   avis_chef_departement, Mobilite
            FROM dossiers WHERE NumEtu = :numetu LIMIT 1
        ");
            $stmt->execute([':numetu' => $numEtu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($result) ? $result : null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * Creates a new dossier.
     * * @param array<string, mixed> $data
     * @return bool
     */
    public function create(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dossiers (
                    NumEtu, Nom, Prenom, DateNaissance, Sexe, Adresse, CodePostal, Ville,
                    EmailPersonnel, EmailAMU, Telephone, CodeDepartement, Composante, Type, Zone, Pays,
                    Campus, Discipline, NiveauEtude, Formation, MoyenneBac, MoyenneSansBac, AvisDRI,
                    DateDebut, MobiliteAnterieure, 0, :PiecesJustificatives, :status
                ) VALUES (
                    :NumEtu, :Nom, :Prenom, :DateNaissance, :Sexe, :Adresse, :CodePostal, :Ville,
                    :EmailPersonnel, :EmailAMU, :Telephone, :CodeDepartement, :Composante, :Type, :Zone, :Pays,
                    :Campus, :Discipline, :NiveauEtude, :Formation, :MoyenneBac, :MoyenneSansBac, :AvisDRI,
                    :DateDebut, :MobiliteAnterieure, 0, :PiecesJustificatives, :status
                )
            ");
            return $stmt->execute([
                ':NumEtu'               => $data['NumEtu']               ?? null,
                ':Nom'                  => $data['Nom']                  ?? null,
                ':Prenom'               => $data['Prenom']               ?? null,
                ':DateNaissance'        => $data['DateNaissance']        ?? null,
                ':Sexe'                 => $data['Sexe']                 ?? null,
                ':Adresse'              => $data['Adresse']              ?? null,
                ':CodePostal'           => $data['CodePostal']           ?? null,
                ':Ville'                => $data['Ville']                ?? null,
                ':EmailPersonnel'       => $data['EmailPersonnel']       ?? null,
                ':EmailAMU'             => $data['EmailAMU']             ?? null,
                ':Telephone'            => $data['Telephone']            ?? null,
                ':CodeDepartement'      => $data['CodeDepartement']      ?? null,
                ':Composante'           => $data['Composante']           ?? null,
                ':Type'                 => $data['Type']                 ?? null,
                ':Zone'                 => $data['Zone']                 ?? null,
                ':Pays'                 => $data['Pays']                 ?? null,
                ':Campus'               => $data['Campus']               ?? null,
                ':Discipline'           => $data['Discipline']           ?? null,
                ':NiveauEtude'          => $data['NiveauEtude']          ?? null,
                ':Formation'            => $data['Formation']            ?? null,
                ':MoyenneBac'           => $data['MoyenneBac']           ?? null,
                ':MoyenneSansBac'       => $data['MoyenneSansBac']       ?? null,
                ':AvisDRI'              => $data['AvisDRI']              ?? null,
                ':DateDebut'            => $data['DateDebut']            ?? null,
                ':MobiliteAnterieure'   => $data['MobiliteAnterieure']   ?? null,
                ':PiecesJustificatives' => $data['PiecesJustificatives'] ?? '{}',
                ':status'               => $data['status']               ?? 'depot',
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Updates an existing dossier.
     * * @param string $numEtu Student number.
     * @param array<string, mixed> $data Key-value pairs to update.
     * @return bool
     */
    public function update(string $numEtu, array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE dossiers SET
                    Nom = COALESCE(:Nom, Nom),
                    Prenom = COALESCE(:Prenom, Prenom),
                    DateNaissance = COALESCE(:DateNaissance, DateNaissance),
                    Sexe = COALESCE(:Sexe, Sexe),
                    Adresse = COALESCE(:Adresse, Adresse),
                    CodePostal = COALESCE(:CodePostal, CodePostal),
                    Ville = COALESCE(:Ville, Ville),
                    EmailPersonnel = COALESCE(:EmailPersonnel, EmailPersonnel),
                    EmailAMU = COALESCE(:EmailAMU, EmailAMU),
                    Telephone = COALESCE(:Telephone, Telephone),
                    CodeDepartement = COALESCE(:CodeDepartement, CodeDepartement),
                    Composante = COALESCE(:Composante, Composante),
                    Type = COALESCE(:Type, Type),
                    Zone = COALESCE(:Zone, Zone),
                    Pays = COALESCE(:Pays, Pays),
                    Mobilite = COALESCE(:Mobilite, Mobilite),
                    Campus = COALESCE(:Campus, Campus),
                    Discipline = COALESCE(:Discipline, Discipline),
                    NiveauEtude = COALESCE(:NiveauEtude, NiveauEtude),
                    Formation = COALESCE(:Formation, Formation),
                    MoyenneBac = COALESCE(:MoyenneBac, MoyenneBac),
                    MoyenneSansBac = COALESCE(:MoyenneSansBac, MoyenneSansBac),
                    AvisDRI = COALESCE(:AvisDRI, AvisDRI),
                    DateDebut = COALESCE(:DateDebut, DateDebut),
                    MobiliteAnterieure = COALESCE(:MobiliteAnterieure, MobiliteAnterieure),
                    PiecesJustificatives = COALESCE(:PiecesJustificatives, PiecesJustificatives),
                    status = COALESCE(:status, status),
                    ModifiePar = COALESCE(:ModifiePar, ModifiePar),
                    ModifieLe  = COALESCE(:ModifieLe,  ModifieLe)
                WHERE NumEtu = :NumEtu
            ");
            $data[':NumEtu'] = $numEtu;
            return $stmt->execute($data);
        } catch (\PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    // ===========================================================
    // AVIS CHEF DE DÉPARTEMENT
    // ===========================================================

    /**
     * Records the decision from the department head.
     * * @param string $numEtu
     * @param string|null $avis 'accepte', 'refuse' or null to reset.
     * @return bool
     */
    public function setAvisChef(string $numEtu, ?string $avis): bool
    {
        $allowed = ['accepte', 'refuse', null];
        if (!in_array($avis, $allowed, true)) return false;
        try {
            $stmt = $this->db->prepare("
                UPDATE dossiers
                SET avis_chef_departement = :avis
                WHERE NumEtu = :numetu
            ");
            return $stmt->execute([':avis' => $avis, ':numetu' => $numEtu]);
        } catch (\PDOException $e) {
            error_log("setAvisChef Error: " . $e->getMessage());
            return false;
        }
    }

    // ===========================================================
    // STATUS
    // ===========================================================

    /**
     * Toggles the "IsComplete" boolean status of a folder.
     * * @param string $numEtu
     * @return bool
     */
    public function toggleCompleteStatus(string $numEtu): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT IsComplete FROM dossiers WHERE NumEtu = :numetu");
            $stmt->execute([':numetu' => $numEtu]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($current)) return false;
            $newStatus = (($current['IsComplete'] ?? 0) == 1) ? 0 : 1;
            $stmt = $this->db->prepare("UPDATE dossiers SET IsComplete = :status WHERE NumEtu = :numetu");
            return $stmt->execute([':status' => $newStatus, ':numetu' => $numEtu]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Proxy for toggleCompleteStatus.
     */
    public function toggleStatus(string $numEtu): bool
    {
        return $this->toggleCompleteStatus($numEtu);
    }

    /**
     * Explicitly sets the workflow status of a folder.
     * * @param string $numEtu
     * @param string $status 'depot', 'instruction', 'accepte', 'refuse'.
     * @return bool
     */
    public function setStatus(string $numEtu, string $status): bool
    {
        $allowed = ['depot', 'instruction', 'accepte', 'refuse'];
        if (!in_array($status, $allowed, true)) return false;
        try {
            $stmt = $this->db->prepare("UPDATE dossiers SET status = :status WHERE NumEtu = :numetu");
            return $stmt->execute([':status' => $status, ':numetu' => $numEtu]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Cycles through available statuses in order.
     * * @param string $numEtu
     * @return bool
     */
    public function cycleStatus(string $numEtu): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT status FROM dossiers WHERE NumEtu = :numetu");
            $stmt->execute([':numetu' => $numEtu]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($current)) return false;
            $statuses      = ['depot', 'instruction', 'accepte', 'refuse'];
            $currentStatus = strtolower(trim(is_string($current['status'] ?? null) ? $current['status'] : 'depot'));
            $currentIndex  = array_search($currentStatus, $statuses, true);
            $nextIndex     = ($currentIndex === false || $currentIndex === count($statuses) - 1) ? 0 : $currentIndex + 1;
            $stmt = $this->db->prepare("UPDATE dossiers SET status = :status WHERE NumEtu = :numetu");
            return $stmt->execute([':status' => $statuses[$nextIndex], ':numetu' => $numEtu]);
        } catch (\PDOException $e) {
            error_log("cycleStatus Error: " . $e->getMessage());
            return false;
        }
    }

    // ===========================================================
    // PAGINATION & SEARCH
    // ===========================================================

    /**
     * Performs a filtered search with pagination.
     * * @param array<string, mixed> $filters Filter parameters (search, type, zone, etc).
     * @param int $page Current page number.
     * @param int $perPage Items per page.
     * @return array{data: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function searchWithPagination(array $filters, int $page, int $perPage): array
    {
        $params = [];
        $where  = " WHERE 1=1";

        if (isset($filters['complet']) && $filters['complet'] !== 'all') {
            $where .= ($filters['complet'] == '1') ? " AND IsComplete = 1" : " AND (IsComplete = 0 OR IsComplete IS NULL)";
        }
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $where .= " AND LOWER(Type) = LOWER(:type)";
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['mobilite']) && $filters['mobilite'] !== 'all') {
            $valid = $this->validMobilite(is_string($filters['mobilite']) ? $filters['mobilite'] : null);
            if ($valid) {
                $where .= " AND Mobilite = :mobilite";
                $params['mobilite'] = $valid;
            }
        }
        if (!empty($filters['zone']) && $filters['zone'] !== 'all') {
            $where .= " AND LOWER(Zone) = LOWER(:zone)";
            $params['zone'] = $filters['zone'];
        }
        if (!empty($filters['composante']) && $filters['composante'] !== 'all') {
            $where .= " AND LOWER(Composante) LIKE LOWER(:composante)";
            $params['composante'] = '%' . $filters['composante'] . '%';
        }
        if (!empty($filters['accord']) && $filters['accord'] !== 'all') {
            $where .= " AND LOWER(Composante) LIKE LOWER(:accord)";
            $params['accord'] = '%' . $filters['accord'] . '%';
        }
        if (!empty($filters['search'])) {
            $s = $filters['search'] . '%';
            $where .= " AND (Nom LIKE :s1 OR Prenom LIKE :s2 OR NumEtu LIKE :s3 OR EmailPersonnel LIKE :s4)";
            $params['s1'] = $s; $params['s2'] = $s; $params['s3'] = $s; $params['s4'] = $s;
        }
        if (!empty($filters['departement']) && $filters['departement'] !== 'all') {
            $where .= " AND LOWER(CodeDepartement) = LOWER(:departement)";
            $params['departement'] = $filters['departement'];
        }

        $totalCount = 0;
        try {
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM dossiers" . $where);
            foreach ($params as $key => $value) $countStmt->bindValue(':' . $key, $value);
            $countStmt->execute();
            $row = $countStmt->fetch(PDO::FETCH_ASSOC);
            $totalCount = (is_array($row) && isset($row['total']) && is_numeric($row['total'])) ? (int) $row['total'] : 0;
        } catch (\PDOException $e) {}

        $limitClause = $perPage > 0 ? " LIMIT :limit OFFSET :offset" : "";

        $sql = "SELECT NumEtu, Nom, Prenom, EmailPersonnel as email, Telephone, Type, Zone, Pays,
                       Campus, Discipline, NiveauEtude, Formation, MoyenneBac, MoyenneSansBac, AvisDRI,
                       DateDebut, MobiliteAnterieure, DateNaissance, Sexe, Adresse, CodePostal, Ville,
                       EmailAMU, CodeDepartement, Composante, IsComplete, PiecesJustificatives, status, Mobilite
                FROM dossiers" . $where . " ORDER BY Nom ASC, Prenom ASC" . $limitClause;

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) { $stmt->bindValue(":$key", $value); }
            if ($perPage > 0) {
                $stmt->bindValue(':limit',  $perPage,               PDO::PARAM_INT);
                $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            if (!empty($data)) {
                $numEtus = array_column($data, 'NumEtu');
                $inQuery = implode(',', array_fill(0, count($numEtus), '?'));
                
                $relancesStmt = $this->db->prepare("SELECT numetu, COUNT(*) as cnt FROM relances WHERE numetu IN ($inQuery) GROUP BY numetu");
                $relancesStmt->execute($numEtus);
                
                $counts = [];
                while ($row = $relancesStmt->fetch(PDO::FETCH_ASSOC)) {
                    if (is_array($row) && isset($row['numetu'], $row['cnt'])) {
                        $counts[(string)$row['numetu']] = (int)$row['cnt'];
                    }
                }
                
                foreach ($data as &$row) {
                    $row['nb_relances'] = $counts[$row['NumEtu']] ?? 0;
                }
            }

            $totalPages = ($perPage > 0 && $totalCount > 0) ? (int) ceil($totalCount / $perPage) : 1;
            return ['data' => $data, 'total' => $totalCount, 'totalPages' => $totalPages];
            
        } catch (\PDOException $e) {
            error_log("SQL Error searchWithPagination : " . $e->getMessage() . " | SQL: " . $sql);
            return ['data' => [], 'total' => 0, 'totalPages' => 0];
        }
    }

    // ===========================================================
    // IMPORT & USER ACCOUNTS
    // ===========================================================

    /**
     * Batch inserts or updates dossiers and creates associated student accounts.
     * * @param array<int, array<string, mixed>> $dossiers List of dossier data.
     * @return int Number of processed records.
     */
    public function upsertMultiple(array $dossiers): int
    {
        $pdo = Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            $stmtEtu = $pdo->prepare("
                INSERT INTO etudiants (
                    numetu, nom, prenom, email, password, departement, campus, annee_etude, type_etudiant, telephone, adresse, code_postal, ville, sexe, date_naissance, created_at
                ) VALUES (
                    :numetu, :nom, :prenom, :email, :pass, :dept, :campus, :annee, :type, :tel, :adr, :cp, :ville, :sexe, :dob, NOW()
                ) ON DUPLICATE KEY UPDATE
                    nom = IF(VALUES(nom) = 'INCONNU', etudiants.nom, VALUES(nom)),
                    prenom = IF(VALUES(prenom) = '-', etudiants.prenom, VALUES(prenom)),
                    email = IF(VALUES(email) IS NULL, etudiants.email, VALUES(email)),
                    departement = IF(VALUES(departement) IS NULL, etudiants.departement, VALUES(departement)),
                    campus = IF(VALUES(campus) IS NULL, etudiants.campus, VALUES(campus)),
                    type_etudiant = VALUES(type_etudiant)
            ");

            $stmtDossier = $pdo->prepare("
                INSERT INTO dossiers (
                    NumEtu, Nom, Prenom, DateNaissance, Sexe, Adresse, CodePostal, Ville,
                    EmailPersonnel, EmailAMU, Telephone, CodeDepartement, Composante, Type, Zone, Pays,
                    Campus, Discipline, NiveauEtude, Formation, MoyenneBac, MoyenneSansBac, AvisDRI,
                    DateDebut, MobiliteAnterieure, IsComplete, PiecesJustificatives, status
                ) VALUES (
                    :NumEtu, :Nom, :Prenom, :DateNaissance, :Sexe, :Adresse, :CodePostal, :Ville,
                    :EmailPersonnel, :EmailAMU, :Telephone, :CodeDepartement, :Composante, :Type, :Zone, :Pays,
                    :Campus, :Discipline, :NiveauEtude, :Formation, :MoyenneBac, :MoyenneSansBac, :AvisDRI,
                    :DateDebut, :MobiliteAnterieure, 0, '{}', 'depot'
                ) ON DUPLICATE KEY UPDATE
                    Nom = IF(VALUES(Nom) = 'INCONNU', dossiers.Nom, VALUES(Nom)),
                    Prenom = IF(VALUES(Prenom) = '-', dossiers.Prenom, VALUES(Prenom)),
                    DateNaissance = COALESCE(VALUES(DateNaissance), dossiers.DateNaissance),
                    Sexe = COALESCE(VALUES(Sexe), dossiers.Sexe),
                    Adresse = IF(VALUES(Adresse) != '', VALUES(Adresse), dossiers.Adresse),
                    CodePostal = IF(VALUES(CodePostal) != '', VALUES(CodePostal), dossiers.CodePostal),
                    Ville = IF(VALUES(Ville) != '', VALUES(Ville), dossiers.Ville),
                    EmailPersonnel = IF(VALUES(EmailPersonnel) != '', VALUES(EmailPersonnel), dossiers.EmailPersonnel),
                    EmailAMU = IF(VALUES(EmailAMU) != '', VALUES(EmailAMU), dossiers.EmailAMU),
                    Telephone = IF(VALUES(Telephone) != '', VALUES(Telephone), dossiers.Telephone),
                    CodeDepartement = IF(VALUES(CodeDepartement) != '', VALUES(CodeDepartement), dossiers.CodeDepartement),
                    Composante = IF(VALUES(Composante) != '', VALUES(Composante), dossiers.Composante),
                    Type = IF(VALUES(Type) != '', VALUES(Type), dossiers.Type),
                    Zone = IF(VALUES(Zone) != '', VALUES(Zone), dossiers.Zone),
                    Pays = IF(VALUES(Pays) != '', VALUES(Pays), dossiers.Pays),
                    Campus = IF(VALUES(Campus) != '', VALUES(Campus), dossiers.Campus),
                    Discipline = IF(VALUES(Discipline) != '', VALUES(Discipline), dossiers.Discipline),
                    NiveauEtude = IF(VALUES(NiveauEtude) != '', VALUES(NiveauEtude), dossiers.NiveauEtude),
                    Formation = IF(VALUES(Formation) != '', VALUES(Formation), dossiers.Formation),
                    MoyenneBac = IF(VALUES(MoyenneBac) != '', VALUES(MoyenneBac), dossiers.MoyenneBac),
                    MoyenneSansBac = IF(VALUES(MoyenneSansBac) != '', VALUES(MoyenneSansBac), dossiers.MoyenneSansBac),
                    AvisDRI = IF(VALUES(AvisDRI) != '', VALUES(AvisDRI), dossiers.AvisDRI),
                    DateDebut = IF(VALUES(DateDebut) != '', VALUES(DateDebut), dossiers.DateDebut),
                    MobiliteAnterieure = IF(VALUES(MobiliteAnterieure) != '', VALUES(MobiliteAnterieure), dossiers.MobiliteAnterieure)
            ");

            $count = 0;
            foreach ($dossiers as $d) {
                $numEtu     = strval($d['NumEtu'] ?? '');
                $email      = !empty($d['EmailAMU']) ? strval($d['EmailAMU']) : (!empty($d['EmailPersonnel']) ? strval($d['EmailPersonnel']) : null);
                $hashedPass = password_hash("Amu" . $numEtu . "!", PASSWORD_DEFAULT);
                $typeEtu    = strtolower(strval($d['Type'] ?? ''));

                $stmtEtu->execute([
                    ':numetu' => $numEtu,
                    ':nom'    => strval($d['Nom']    ?: 'INCONNU'),
                    ':prenom' => strval($d['Prenom'] ?: '-'),
                    ':email'  => $email,
                    ':pass'   => $hashedPass,
                    ':dept'   => $d['CodeDepartement'] ? strval($d['CodeDepartement']) : null,
                    ':campus' => $d['Campus']          ? strval($d['Campus'])          : null,
                    ':annee'  => $d['NiveauEtude']     ? strval($d['NiveauEtude'])     : null,
                    ':type'   => ($typeEtu === 'entrant') ? 'entrant' : 'sortant',
                    ':tel'    => $d['Telephone']       ? strval($d['Telephone'])       : null,
                    ':adr'    => $d['Adresse']         ? strval($d['Adresse'])         : null,
                    ':cp'     => $d['CodePostal']      ? strval($d['CodePostal'])      : null,
                    ':ville'  => $d['Ville']           ? strval($d['Ville'])           : null,
                    ':sexe'   => $d['Sexe']            ? strval($d['Sexe'])            : null,
                    ':dob'    => $d['DateNaissance']   ? strval($d['DateNaissance'])   : null,
                ]);

                $stmtDossier->execute([
                    ':NumEtu'             => $numEtu,
                    ':Nom'                => strval($d['Nom']    ?? ''),
                    ':Prenom'             => strval($d['Prenom'] ?? ''),
                    ':DateNaissance'      => $d['DateNaissance']   ? strval($d['DateNaissance'])   : null,
                    ':Sexe'               => $d['Sexe']            ? strval($d['Sexe'])            : null,
                    ':Adresse'            => strval($d['Adresse']         ?? ''),
                    ':CodePostal'         => strval($d['CodePostal']      ?? ''),
                    ':Ville'              => strval($d['Ville']           ?? ''),
                    ':EmailPersonnel'     => strval($d['EmailPersonnel']  ?? ''),
                    ':EmailAMU'           => strval($d['EmailAMU']        ?? ''),
                    ':Telephone'          => strval($d['Telephone']       ?? ''),
                    ':CodeDepartement'    => strval($d['CodeDepartement'] ?? ''),
                    ':Composante'         => strval($d['Composante']      ?? ''),
                    ':Type'               => !empty($d['Type']) ? strval($d['Type']) : 'sortant',
                    ':Zone'               => !empty($d['Zone']) ? strval($d['Zone']) : 'europe',
                    ':Pays'               => strval($d['Pays']            ?? ''),
                    ':Campus'             => strval($d['Campus']          ?? ''),
                    ':Discipline'         => strval($d['Discipline']      ?? ''),
                    ':NiveauEtude'        => strval($d['NiveauEtude']     ?? ''),
                    ':Formation'          => strval($d['Formation']       ?? ''),
                    ':MoyenneBac'         => strval($d['MoyenneBac']      ?? ''),
                    ':MoyenneSansBac'     => strval($d['MoyenneSansBac']  ?? ''),
                    ':AvisDRI'            => strval($d['AvisDRI']         ?? ''),
                    ':DateDebut'          => strval($d['DateDebut']       ?? ''),
                    ':MobiliteAnterieure' => strval($d['MobiliteAnterieure'] ?? ''),
                ]);
                $count++;
            }

            $pdo->commit();
            return $count;
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("DB Upsert Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Fetches all unique departments from the dossiers table.
     * @return array<int, string>
     */
    public function getAllDepartements(): array
    {
        $sql = "SELECT DISTINCT CodeDepartement
            FROM dossiers
            WHERE CodeDepartement IS NOT NULL
              AND CodeDepartement != ''
            ORDER BY CodeDepartement ASC";

        $stmt = $this->db->query($sql);
        if ($stmt === false) return [];

        $departments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row) && isset($row['CodeDepartement']) && is_scalar($row['CodeDepartement'])) {
                $departments[] = (string) $row['CodeDepartement'];
            }
        }
        return $departments;
    }

    // ===========================================================
    // VALIDATION DOCUMENTS
    // ===========================================================

    /**
     * Checks which required documents are missing or present.
     * * @param string $numetu
     * @return array{manquants: array<int, string>, presents: array<int, string>, statuts: array<string, string>}
     */
    public function analyserDocuments(string $numetu): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT PiecesJustificatives, StatutDocuments, Type
                FROM dossiers
                WHERE NumEtu = :numetu
                LIMIT 1
            ");
            $stmt->execute([':numetu' => $numetu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($result)) {
                return ['manquants' => [], 'presents' => [], 'statuts' => []];
            }

            $piecesJson = $result['PiecesJustificatives'] ?? '{}';
            $pieces     = json_decode($piecesJson, true) ?? [];
            if (!is_array($pieces)) $pieces = [];

            $documentsRequis = ['photo', 'cv'];
            $type = strtolower(trim(is_string($result['Type'] ?? null) ? $result['Type'] : ''));

            if (!empty($pieces['convention']) || $type === 'sortant') {
                $documentsRequis[] = 'convention';
            } elseif (!empty($pieces['lettre_motivation']) || $type === 'entrant') {
                $documentsRequis[] = 'lettre_motivation';
            } else {
                if (empty($pieces['convention']))        $documentsRequis[] = 'convention';
                if (empty($pieces['lettre_motivation'])) $documentsRequis[] = 'lettre_motivation';
            }

            $documentsRequis[] = 'langues';

            $manquants = [];
            $presents  = [];
            foreach ($documentsRequis as $doc) {
                if (empty($pieces[$doc])) {
                    $manquants[] = $doc;
                } else {
                    $presents[] = $doc;
                }
            }

            $statutsJson    = $result['StatutDocuments'] ?? '{}';
            $decodedStatuts = is_string($statutsJson) && $statutsJson !== ''
                ? (json_decode($statutsJson, true) ?? [])
                : [];

            $statuts = [];
            if (is_array($decodedStatuts)) {
                foreach ($decodedStatuts as $key => $val) {
                    if (is_scalar($key) && is_scalar($val)) {
                        $statuts[(string)$key] = (string)$val;
                    }
                }
            }

            return ['manquants' => $manquants, 'presents' => $presents, 'statuts' => $statuts];

        } catch (\PDOException $e) {
            error_log("analyserDocuments Error: " . $e->getMessage());
            return ['manquants' => [], 'presents' => [], 'statuts' => []];
        }
    }

    /**
     * Saves the validation status and metadata for uploaded documents.
     * * @param string $numetu
     * @param array<string, string> $statutsDocuments Validation states (e.g. 'validé', 'refusé').
     * @param string|null $dateLimite Deadline for correction.
     * @param string|null $commentaire Admin comments.
     * @return bool
     */
    public function enregistrerValidation(
        string $numetu,
        array $statutsDocuments,
        ?string $dateLimite = null,
        ?string $commentaire = null
    ): bool {
        try {
            if ($dateLimite !== null && !empty($dateLimite)) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateLimite);
                if (!$date || $date->format('Y-m-d') !== $dateLimite) {
                    error_log("Invalid deadline date: $dateLimite");
                    return false;
                }
            } else {
                $dateLimite = null;
            }

            $stmt = $this->db->prepare("
                UPDATE dossiers
                SET
                    DateLimite = :dateLimite,
                    CommentaireAdmin = :commentaire,
                    StatutDocuments = :statuts
                WHERE NumEtu = :numetu
            ");

            return $stmt->execute([
                ':dateLimite'  => $dateLimite,
                ':commentaire' => $commentaire,
                ':statuts'     => json_encode($statutsDocuments),
                ':numetu'      => $numetu,
            ]);

        } catch (\PDOException $e) {
            error_log("enregistrerValidation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finds folders that are marked as incomplete.
     * * @return array<int, array<string, mixed>>
     */
    public function findIncompleteFolders(): array
    {
        try {
            $stmt = $this->db->query("
            SELECT NumEtu, Nom, Prenom, EmailAMU, EmailPersonnel
            FROM dossiers
            WHERE IsComplete = 0 OR IsComplete IS NULL
            ORDER BY Nom, Prenom
        ");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\PDOException $e) {
            error_log("findIncompleteFolders Error: " . $e->getMessage());
            return [];
        }
    }
}