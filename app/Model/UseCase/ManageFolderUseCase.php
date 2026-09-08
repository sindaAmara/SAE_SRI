<?php

namespace Model\UseCase;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Model\Repository\FolderRepositoryInterface;
use Model\Persistence\FolderRepositoryPDO;

/**
 * ManageFolderUseCase
 *
 * Use case that centralises all operations on student application folders (dossiers).
 *
 * Responsibilities include retrieving, creating, updating, and deleting folders,
 * managing per-document statuses and supporting files, cycling workflow statuses,
 * recording the department head's opinion, and bulk-importing folders from CSV
 * or Excel spreadsheet files.
 */
class ManageFolderUseCase
{
    /** @var FolderRepositoryInterface Repository used for all folder persistence operations */
    private FolderRepositoryInterface $dossierRepo;

    /**
     * @param FolderRepositoryInterface|null $repo Repository to use; defaults to FolderRepositoryPDO when null
     */
    public function __construct(?FolderRepositoryInterface $repo = null)
    {
        $this->dossierRepo = $repo ?? new FolderRepositoryPDO();
    }

    /**
     * Returns all student folders from the repository.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllFolders(): array
    {
        return $this->dossierRepo->findAll();
    }

    /**
     * Returns the full details of a student's folder, including decoded supporting
     * documents and document statuses.
     *
     * Supporting documents stored as bare file paths (legacy format) are
     * automatically normalised to the canonical array structure
     * {'file', 'status', 'comment'}.
     *
     * @param string $numetu Student number
     * @return array<string, mixed>|null The folder data array, or null if not found
     */
    public function getStudentDetails(string $numetu): ?array
    {
        $result = $this->dossierRepo->findByNumEtu($numetu);
        if (!$result) return null;

        $piecesJson = $result['PiecesJustificatives'] ?? '';
        $pieces = (is_string($piecesJson) && $piecesJson !== '') ? (json_decode($piecesJson, true) ?? []) : [];
        if (!is_array($pieces)) $pieces = [];

        foreach ($pieces as $key => $val) {
            if (is_string($val)) {
                $pieces[$key] = ['file' => $val, 'status' => 'pending', 'comment' => ''];
            }
        }
        $result['pieces'] = $pieces;

        $statutsJson = $result['StatutDocuments'] ?? '';
        $statuts = (is_string($statutsJson) && $statutsJson !== '') ? (json_decode($statutsJson, true) ?? []) : [];
        if (!is_array($statuts)) $statuts = [];
        $result['statuts'] = $statuts;

        return $result;
    }

    /**
     * Alias for getStudentDetails().
     *
     * @param string $numetu Student number
     * @return array<string, mixed>|null The folder data array, or null if not found
     */
    public function getByNumetu(string $numetu): ?array
    {
        return $this->getStudentDetails($numetu);
    }

    /**
     * Toggles the completion flag of the folder identified by the given student number.
     *
     * @param string $numetu Student number
     * @return bool True on success, false on failure
     */
    public function toggleCompleteStatus(string $numetu): bool
    {
        return $this->dossierRepo->toggleCompleteStatus($numetu);
    }

    /**
     * Sets the workflow status of a folder to a specific value.
     *
     * @param string $numEtu  Student number identifying the folder
     * @param string $status  The new status value to apply
     * @return bool True on success, false on failure
     */
    public function setFolderStatus(string $numEtu, string $status): bool
    {
        return $this->dossierRepo->setStatus($numEtu, $status);
    }

    /**
     * Advances the workflow status of a folder to the next step in the defined cycle.
     *
     * @param string $numEtu Student number identifying the folder
     * @return bool True on success, false on failure
     */
    public function cycleFolderStatus(string $numEtu): bool
    {
        return $this->dossierRepo->cycleStatus($numEtu);
    }

    /**
     * Records the department head's opinion on a student folder.
     *
     * Accepted values for $avis are 'accepte', 'refuse', or null (reset).
     * When the opinion is 'refuse', the folder's global workflow status is
     * automatically set to 'refuse' as well.
     * When the opinion is 'accepte', the global status is left unchanged —
     * final validation remains the administrator's responsibility.
     *
     * @param string      $numEtu Student number identifying the folder
     * @param string|null $avis   The department head's opinion ('accepte', 'refuse', or null)
     * @return bool True on success, false on failure
     */
    public function setAvisChef(string $numEtu, ?string $avis): bool
    {
        $result = $this->dossierRepo->setAvisChef($numEtu, $avis);

        if ($result && $avis === 'refuse') {
            $this->dossierRepo->setStatus($numEtu, 'refuse');
        }

        return $result;
    }

    /**
     * Searches folders using the given filters and returns a paginated result set.
     *
     * @param array<string, mixed> $filters  Associative array of filter criteria
     * @param int                  $page     Page number (1-based)
     * @param int                  $perPage  Number of results per page
     * @return array{data: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function rechercherAvecPagination(array $filters, int $page = 1, int $perPage = 10): array
    {
        return $this->dossierRepo->searchWithPagination($filters, $page, $perPage);
    }

    /**
     * Searches folders using the given filters and returns the full result set without pagination.
     *
     * Internally calls the repository's paginated search with page 1 and perPage 0,
     * which is treated as "no limit" by the repository implementation.
     *
     * @param array<string, mixed> $filters Associative array of filter criteria
     * @return array{data: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function searchWithoutPagination(array $filters): array
    {
        return $this->dossierRepo->searchWithPagination($filters, 1, 0);
    }

    /**
     * Updates the status and optional file content of a specific supporting document
     * within a student folder.
     *
     * Retrieves the existing folder, merges the new status and comment into the
     * PiecesJustificatives JSON, optionally replaces the stored file with the
     * base64-encoded content of $fileContent, persists the updated pieces via
     * the repository's update method, and saves the document status map via
     * enregistrerValidation.
     *
     * @param string      $numEtu      Student number identifying the folder
     * @param string      $docType     Key identifying the document type (e.g. 'cv', 'photo')
     * @param string      $status      New validation status for the document
     * @param string      $comment     Admin comment to attach to the document
     * @param string|null $fileContent Raw file content to store (base64-encoded internally), or null to keep existing
     * @return bool True if both the pieces update and the validation recording succeeded, false otherwise
     */
    public function updateDocumentStatus(string $numEtu, string $docType, string $status, string $comment, ?string $fileContent = null): bool
    {
        $dossier = $this->getStudentDetails($numEtu);
        if (!$dossier) return false;

        $pieces = isset($dossier['pieces']) && is_array($dossier['pieces']) ? $dossier['pieces'] : [];
        if (!isset($pieces[$docType])) {
            $pieces[$docType] = ['file' => '', 'status' => $status, 'comment' => $comment];
        } else {
            $pieces[$docType]['status']  = $status;
            $pieces[$docType]['comment'] = $comment;
        }

        if ($fileContent !== null && $fileContent !== false) {
            $pieces[$docType]['file'] = base64_encode($fileContent);
        }

        $formattedData = [
            ':Nom'                => null,
            ':Prenom'             => null,
            ':DateNaissance'      => null,
            ':Sexe'               => null,
            ':Adresse'            => null,
            ':CodePostal'         => null,
            ':Ville'              => null,
            ':EmailPersonnel'     => null,
            ':EmailAMU'           => null,
            ':Telephone'          => null,
            ':CodeDepartement'    => null,
            ':Composante'         => null,
            ':Type'               => null,
            ':Zone'               => null,
            ':Pays'               => null,
            ':Mobilite'           => null,
            ':Campus'             => null,
            ':Discipline'         => null,
            ':NiveauEtude'        => null,
            ':Formation'          => null,
            ':MoyenneBac'         => null,
            ':MoyenneSansBac'     => null,
            ':AvisDRI'            => null,
            ':DateDebut'          => null,
            ':MobiliteAnterieure' => null,
            ':PiecesJustificatives' => json_encode($pieces),
            ':status'             => null,
            ':ModifiePar'         => null,
            ':ModifieLe'          => null,
        ];

        $updatedPieces = $this->dossierRepo->update($numEtu, $formattedData);

        $statuts = isset($dossier['statuts']) && is_array($dossier['statuts']) ? $dossier['statuts'] : [];
        $statuts[$docType] = $status;

        $dateLimite = isset($dossier['DateLimite']) && is_scalar($dossier['DateLimite'])
            ? (string) $dossier['DateLimite']
            : null;

        $commentaireGlobal = isset($dossier['CommentaireAdmin']) && is_scalar($dossier['CommentaireAdmin'])
            ? (string) $dossier['CommentaireAdmin']
            : null;

        $updatedStatuts = $this->dossierRepo->enregistrerValidation($numEtu, $statuts, $dateLimite, $commentaireGlobal);

        return $updatedPieces && $updatedStatuts;
    }

    /**
     * Creates a new student folder from the given data array.
     *
     * Accepts both snake_case and PascalCase field names. Uploaded file fields
     * (photo, cv, convention, lettre_motivation, langues_file) are base64-encoded
     * and stored in the PiecesJustificatives JSON with a 'pending' initial status.
     * Empty string values are normalised to null before persistence.
     *
     * @param array<string, mixed> $data Folder data; supports both snake_case and PascalCase keys
     * @return bool True on success, false on failure
     */
    public function creerDossier(array $data): bool
    {
        $pieces    = [];
        $addPiece = function (?string $fileData): ?array {
            return $fileData !== null
                ? ['file' => base64_encode($fileData), 'status' => 'pending', 'comment' => '']
                : null;
        };

        if (!empty($data['photo']))             $pieces['photo']             = $addPiece(is_string($data['photo']) ? $data['photo'] : null);
        if (!empty($data['cv']))                $pieces['cv']                = $addPiece(is_string($data['cv']) ? $data['cv'] : null);
        if (!empty($data['convention']))        $pieces['convention']        = $addPiece(is_string($data['convention']) ? $data['convention'] : null);
        if (!empty($data['lettre_motivation'])) $pieces['lettre_motivation'] = $addPiece(is_string($data['lettre_motivation']) ? $data['lettre_motivation'] : null);
        if (!empty($data['langues_file']))      $pieces['langues']           = $addPiece(is_string($data['langues_file']) ? $data['langues_file'] : null);

        $piecesJson = empty($pieces) ? '{}' : json_encode($pieces);

        $rawDate       = $data['naissance'] ?? ($data['DateNaissance'] ?? null);
        $dateNaissance = null;
        if (!empty($rawDate) && is_string($rawDate)) {
            $date = \DateTime::createFromFormat('Y-m-d', $rawDate);
            $dateNaissance = ($date && $date->format('Y-m-d') === $rawDate) ? $rawDate : null;
        }

        $formattedData = [
            'NumEtu'             => $data['numetu']              ?? ($data['NumEtu']             ?? null),
            'Nom'                => $data['nom']                 ?? ($data['Nom']                ?? null),
            'Prenom'             => $data['prenom']              ?? ($data['Prenom']             ?? null),
            'DateNaissance'      => $dateNaissance,
            'Sexe'               => $data['sexe']                ?? ($data['Sexe']               ?? null),
            'Adresse'            => $data['adresse']             ?? ($data['Adresse']            ?? null),
            'CodePostal'         => $data['cp']                  ?? ($data['CodePostal']         ?? null),
            'Ville'              => $data['ville']               ?? ($data['Ville']              ?? null),
            'EmailPersonnel'     => $data['email_perso']         ?? ($data['EmailPersonnel']     ?? null),
            'EmailAMU'           => $data['email_amu']           ?? ($data['EmailAMU']           ?? null),
            'Telephone'          => $data['telephone']           ?? ($data['Telephone']          ?? null),
            'CodeDepartement'    => $data['departement']         ?? ($data['CodeDepartement']    ?? null),
            'Composante'         => $data['composante']          ?? ($data['Composante']         ?? null),
            'Type'               => $data['type']                ?? ($data['Type']               ?? null),
            'Zone'               => $data['zone']                ?? ($data['Zone']               ?? null),
            'Pays'               => $data['pays']                ?? ($data['Pays']               ?? null),
            'Campus'             => $data['campus']              ?? ($data['Campus']             ?? null),
            'Discipline'         => $data['discipline']          ?? ($data['Discipline']         ?? null),
            'NiveauEtude'        => $data['niveau_etude']        ?? ($data['NiveauEtude']        ?? null),
            'Formation'          => $data['formation']           ?? ($data['Formation']          ?? null),
            'MoyenneBac'         => $data['moyenne_bac']         ?? ($data['MoyenneBac']         ?? null),
            'MoyenneSansBac'     => $data['moyenne_sans_bac']    ?? ($data['MoyenneSansBac']     ?? null),
            'AvisDRI'            => $data['avis_dri']            ?? ($data['AvisDRI']            ?? null),
            'DateDebut'          => $data['date_debut']          ?? ($data['DateDebut']          ?? null),
            'MobiliteAnterieure' => $data['mobilite_anterieure'] ?? ($data['MobiliteAnterieure'] ?? null),
            'PiecesJustificatives' => $piecesJson,
            'status'             => $data['status'] ?? 'depot',
        ];

        foreach ($formattedData as $key => $value) {
            if ($value === '') $formattedData[$key] = null;
        }

        return $this->dossierRepo->create($formattedData);
    }

    /**
     * Updates an existing student folder with the given data.
     *
     * Retrieves the current folder, merges any new uploaded files into the
     * existing PiecesJustificatives, normalises empty strings to null
     * (so that readonly HTML fields do not overwrite existing database values),
     * and delegates the update to the repository.
     *
     * @param array<string, mixed> $data Updated folder data; supports both snake_case and PascalCase keys
     * @return bool True on success, false if the folder is not found or the update fails
     */
    public function updateDossier(array $data): bool
    {
        $numEtu = strval($data['numetu'] ?? ($data['NumEtu'] ?? ''));
        if ($numEtu === '') return false;

        $existing = $this->getStudentDetails($numEtu);
        if (!$existing) return false;

        $oldPieces = isset($existing['pieces']) && is_array($existing['pieces']) ? $existing['pieces'] : [];

        $updatePiece = function (array &$arr, string $key, ?string $fileData): void {
            if (!empty($fileData)) {
                $arr[$key] = ['file' => base64_encode($fileData), 'status' => 'pending', 'comment' => ''];
            }
        };

        $updatePiece($oldPieces, 'photo',             isset($data['photo'])             && is_string($data['photo'])             ? $data['photo']             : null);
        $updatePiece($oldPieces, 'cv',                isset($data['cv'])                && is_string($data['cv'])                ? $data['cv']                : null);
        $updatePiece($oldPieces, 'convention',        isset($data['convention'])        && is_string($data['convention'])        ? $data['convention']        : null);
        $updatePiece($oldPieces, 'lettre_motivation', isset($data['lettre_motivation']) && is_string($data['lettre_motivation']) ? $data['lettre_motivation'] : null);
        $updatePiece($oldPieces, 'langues',           isset($data['langues_file'])      && is_string($data['langues_file'])      ? $data['langues_file']      : null);

        $piecesJson = empty($oldPieces) ? '{}' : json_encode($oldPieces);

        $rawDate       = $data['naissance'] ?? ($data['DateNaissance'] ?? null);
        $dateNaissance = null;
        if (!empty($rawDate) && is_string($rawDate)) {
            $date = \DateTime::createFromFormat('Y-m-d', $rawDate);
            $dateNaissance = ($date && $date->format('Y-m-d') === $rawDate) ? $rawDate : null;
        }

        $formattedData = [
            ':Nom'                => $data['nom']                 ?? ($data['Nom']                ?? null),
            ':Prenom'             => $data['prenom']              ?? ($data['Prenom']             ?? null),
            ':DateNaissance'      => $dateNaissance,
            ':Sexe'               => $data['sexe']                ?? ($data['Sexe']               ?? null),
            ':Adresse'            => $data['adresse']             ?? ($data['Adresse']            ?? null),
            ':CodePostal'         => $data['cp']                  ?? ($data['CodePostal']         ?? null),
            ':Ville'              => $data['ville']               ?? ($data['Ville']              ?? null),
            ':EmailPersonnel'     => $data['email_perso']         ?? ($data['EmailPersonnel']     ?? null),
            ':EmailAMU'           => $data['email_amu']           ?? ($data['EmailAMU']           ?? null),
            ':Telephone'          => $data['telephone']           ?? ($data['Telephone']          ?? null),
            ':CodeDepartement'    => $data['departement']         ?? ($data['CodeDepartement']    ?? null),
            ':Composante'         => $data['composante']          ?? ($data['Composante']         ?? null),
            ':Type'               => $data['type']                ?? ($data['Type']               ?? null),
            ':Zone'               => $data['zone']                ?? ($data['Zone']               ?? null),
            ':Pays'               => $data['pays']                ?? ($data['Pays']               ?? null),
            ':Mobilite'           => $data['mobilite']            ?? ($data['Mobilite']           ?? null),
            ':Campus'             => $data['campus']              ?? ($data['Campus']             ?? null),
            ':Discipline'         => $data['discipline']          ?? ($data['Discipline']         ?? null),
            ':NiveauEtude'        => $data['niveau_etude']        ?? ($data['NiveauEtude']        ?? null),
            ':Formation'          => $data['formation']           ?? ($data['Formation']          ?? null),
            ':MoyenneBac'         => $data['moyenne_bac']         ?? ($data['MoyenneBac']         ?? null),
            ':MoyenneSansBac'     => $data['moyenne_sans_bac']    ?? ($data['MoyenneSansBac']     ?? null),
            ':AvisDRI'            => $data['avis_dri']            ?? ($data['AvisDRI']            ?? null),
            ':DateDebut'          => $data['date_debut']          ?? ($data['DateDebut']          ?? null),
            ':MobiliteAnterieure' => $data['mobilite_anterieure'] ?? ($data['MobiliteAnterieure'] ?? null),
            ':PiecesJustificatives' => $piecesJson,
            ':status'             => is_string($existing['status'] ?? null) ? $existing['status'] : 'depot',
            ':ModifiePar'         => !empty($data['ModifiePar']) ? $data['ModifiePar'] : null,
            ':ModifieLe'          => !empty($data['ModifieLe'])  ? $data['ModifieLe']  : null,
        ];

        // ── CORRECTION : remplacer les chaînes vides par null ──────────────
        // Les champs readonly soumettent '' quand ils sont vides côté HTML,
        // ce qui écrasait les valeurs existantes en base avec une chaîne vide.
        // On ne remplace que les '' : une vraie nouvelle valeur non-vide est préservée.
        foreach ($formattedData as $key => $value) {
            if ($value === '') {
                $formattedData[$key] = null;
            }
        }
        // ───────────────────────────────────────────────────────────────────

        error_log("updateDossier :Mobilite = " . ($formattedData[':Mobilite'] ?? 'NULL'));

        return $this->dossierRepo->update($numEtu, $formattedData);
    }

    /**
     * Imports student folders from a CSV or Excel file.
     *
     * Supports .csv files (auto-detects comma vs semicolon delimiter) and any
     * spreadsheet format supported by PhpSpreadsheet (.xlsx, .xls, .ods, etc.).
     * The first row is treated as a header; column positions are resolved through
     * a fuzzy keyword-matching algorithm that handles accented characters, varying
     * separators, and synonym column names. Resolved rows are upserted in bulk.
     *
     * @param string $filePath         Absolute path to the file on disk
     * @param string $originalFileName Original filename including extension, used to detect the file format
     * @return bool True if at least one folder was upserted, false otherwise
     */
    public function importFoldersFromCSV(string $filePath, string $originalFileName = ''): bool
    {
        try {
            $ext  = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            $rows = [];

            if ($ext === 'csv') {
                $oldSetting = ini_get('auto_detect_line_endings');
                ini_set('auto_detect_line_endings', '1');

                if (($handle = fopen($filePath, "r")) !== false) {
                    $firstLine = fgets($handle);
                    $delimiter = substr_count((string)$firstLine, ';') > substr_count((string)$firstLine, ',') ? ';' : ',';
                    rewind($handle);
                    while (($rowData = fgetcsv($handle, 0, $delimiter)) !== false) {
                        $rows[] = $rowData;
                    }
                    fclose($handle);
                }
                ini_set('auto_detect_line_endings', (string)$oldSetting);
            } else {
                $spreadsheet = IOFactory::load($filePath);
                $worksheet   = $spreadsheet->getActiveSheet();
                $rows        = $worksheet->toArray();
            }

            if (empty($rows) || count($rows) < 2) return false;

            $normalize = function (string $string): string {
                $string   = mb_strtolower(trim($string), 'UTF-8');
                $unwanted = [
                    'á'=>'a','à'=>'a','â'=>'a','ä'=>'a','ã'=>'a','å'=>'a',
                    'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
                    'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
                    'ó'=>'o','ò'=>'o','ô'=>'o','ö'=>'o','õ'=>'o',
                    'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
                    'ç'=>'c','ñ'=>'n','œ'=>'oe',
                ];
                $string = strtr($string, $unwanted);
                $string = (string) preg_replace('/[^a-z0-9\s]/', ' ', $string);
                $string = (string) preg_replace('/\s+/', ' ', $string);
                return trim($string);
            };

            $rawHeaders = array_shift($rows);
            if (!is_array($rawHeaders)) return false;
            $headers = array_map(fn($h) => $normalize(is_string($h) ? $h : (string)$h), $rawHeaders);

            $keywords = [
                'NumEtu'             => ['individus identifiant','individu identifiant','identifiant utilisateur','identifiant','individu','numetu','numero etudiant'],
                'Candidat'           => ['candidat nom prenom','candidat'],
                'Nom'                => ['nom de famille','nom'],
                'Prenom'             => ['prenom'],
                'DateNaissance'      => ['naissance','date de naissance'],
                'Sexe'               => ['sexe','genre'],
                'Adresse'            => ['adresse','address','rue'],
                'CodePostal'         => ['code postal','postal','cp'],
                'Ville'              => ['ville','city','commune'],
                'EmailAMU'           => ['amu','institutionnel'],
                'EmailPersonnel'     => ['email personnel','e mail personnel','email','courriel','mail'],
                'Telephone'          => ['telephone','mobile','tel','phone'],
                'CodeDepartement'    => ['departement','filiere'],
                'Composante'         => ['sejour origine','sejour','composante','faculte','institut'],
                'Type'               => ['type de mobilite','type'],
                'Zone'               => ['zone'],
                'Pays'               => ['pays 1','pays','country'],
                'Campus'             => ['campus'],
                'Discipline'         => ['discipline'],
                'NiveauEtude'        => ['niveau d etude','niveau'],
                'Formation'          => ['nom formation','formation'],
                'MoyenneBac'         => ['moyenne avec bac','moyenne bac'],
                'MoyenneSansBac'     => ['moyenne sans bac','moyenne hors'],
                'AvisDRI'            => ['avis globale du departement','avis globale','avis dri','decision dri'],
                'DateDebut'          => ['periode de debut','debut'],
                'MobiliteAnterieure' => ['deja parti','a deja effectue','ayant deja effect','deja effecte','deja effectue','mobilite anterieure'],
            ];

            /** @var array<string, int> $indices */
            $indices = array_fill_keys(array_keys($keywords), -1);

            foreach ($keywords as $field => $searchWords) {
                foreach ($headers as $index => $header) {
                    if ($indices[$field] !== -1) continue;
                    foreach ($searchWords as $word) {
                        if ($header === $word) { $indices[$field] = $index; break 2; }
                    }
                }
            }
            foreach ($keywords as $field => $searchWords) {
                if ($indices[$field] !== -1) continue;
                foreach ($headers as $index => $header) {
                    if (in_array($index, $indices, true)) continue;
                    foreach ($searchWords as $word) {
                        if (strpos($header, $word) === 0) { $indices[$field] = $index; break 2; }
                    }
                }
            }
            foreach ($keywords as $field => $searchWords) {
                if ($indices[$field] !== -1) continue;
                foreach ($headers as $index => $header) {
                    if (in_array($index, $indices, true)) continue;
                    foreach ($searchWords as $word) {
                        if (strpos($header, $word) !== false) { $indices[$field] = $index; break 2; }
                    }
                }
            }

            $getVal = function (string $field, array $rowData) use ($indices): string {
                $idx = $indices[$field];
                return ($idx !== -1 && isset($rowData[$idx])) ? trim(strval($rowData[$idx])) : '';
            };

            $dossiersToInsert = [];

            foreach ($rows as $rowData) {
                if (!is_array($rowData)) continue;

                $numEtu = $getVal('NumEtu', $rowData);
                $numEtu = (string) preg_replace('/[^a-zA-Z0-9]/', '', $numEtu);
                if (empty($numEtu)) continue;

                $candidatVal = $getVal('Candidat', $rowData);
                $nomVal      = $getVal('Nom', $rowData);
                $nom    = '';
                $prenom = '';

                if (!empty($candidatVal)) {
                    if (strpos($candidatVal, ',') !== false) {
                        $parts  = explode(',', $candidatVal, 2);
                        $nom    = strtoupper(trim($parts[0]));
                        $prenom = ucwords(strtolower(trim($parts[1])));
                    } else {
                        $parts = explode(' ', $candidatVal, 2);
                        if (count($parts) === 2 && $indices['Prenom'] === -1) {
                            $nom    = strtoupper(trim($parts[0]));
                            $prenom = ucwords(strtolower(trim($parts[1])));
                        } else {
                            $nom    = strtoupper(trim($candidatVal));
                            $prenom = $getVal('Prenom', $rowData) ?: '-';
                        }
                    }
                } else {
                    $nom    = !empty($nomVal) ? strtoupper(trim($nomVal)) : 'INCONNU';
                    $prenom = $getVal('Prenom', $rowData) ?: '-';
                }

                $rawDate       = $getVal('DateNaissance', $rowData);
                $dateNaissance = null;
                if (!empty($rawDate)) {
                    if (is_numeric($rawDate)) {
                        try {
                            $dateObj       = Date::excelToDateTimeObject((float) $rawDate);
                            $dateNaissance = $dateObj->format('Y-m-d');
                        } catch (\Exception $e) {}
                    }
                    if (!$dateNaissance) {
                        if (preg_match('#^(\d{2})[-/](\d{2})[-/](\d{4})$#', $rawDate, $matches)) {
                            $dateNaissance = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                        } else {
                            $parsed        = strtotime($rawDate);
                            $dateNaissance = ($parsed !== false) ? date('Y-m-d', $parsed) : null;
                        }
                    }
                }

                $moyenneBac     = str_replace(',', '.', $getVal('MoyenneBac',     $rowData));
                $moyenneSansBac = str_replace(',', '.', $getVal('MoyenneSansBac', $rowData));

                $composanteRaw = $getVal('Composante', $rowData);
                if (stripos($composanteRaw, 'IUT') !== false) {
                    $composanteRaw = 'IUT';
                } elseif (stripos($composanteRaw, 'CIVIS') !== false) {
                    $composanteRaw = 'AMU CIVIS';
                }

                $dossiersToInsert[] = [
                    'NumEtu'             => $numEtu,
                    'Nom'                => $nom,
                    'Prenom'             => $prenom,
                    'DateNaissance'      => $dateNaissance,
                    'Sexe'               => $getVal('Sexe',               $rowData),
                    'Adresse'            => $getVal('Adresse',            $rowData),
                    'CodePostal'         => $getVal('CodePostal',         $rowData),
                    'Ville'              => $getVal('Ville',              $rowData),
                    'EmailPersonnel'     => $getVal('EmailPersonnel',     $rowData),
                    'EmailAMU'           => $getVal('EmailAMU',           $rowData),
                    'Telephone'          => $getVal('Telephone',          $rowData),
                    'CodeDepartement'    => $getVal('CodeDepartement',    $rowData),
                    'Composante'         => $composanteRaw,
                    'Type'               => $getVal('Type',               $rowData) ?: 'sortant',
                    'Zone'               => $getVal('Zone',               $rowData) ?: 'europe',
                    'Pays'               => $getVal('Pays',               $rowData),
                    'Campus'             => $getVal('Campus',             $rowData),
                    'Discipline'         => $getVal('Discipline',         $rowData),
                    'NiveauEtude'        => $getVal('NiveauEtude',        $rowData),
                    'Formation'          => $getVal('Formation',          $rowData),
                    'MoyenneBac'         => $moyenneBac,
                    'MoyenneSansBac'     => $moyenneSansBac,
                    'AvisDRI'            => $getVal('AvisDRI',            $rowData),
                    'DateDebut'          => $getVal('DateDebut',          $rowData),
                    'MobiliteAnterieure' => $getVal('MobiliteAnterieure', $rowData),
                ];
            }

            if (!empty($dossiersToInsert)) {
                return $this->dossierRepo->upsertMultiple($dossiersToInsert) > 0;
            }
            return false;

        } catch (\Exception $e) {
            error_log("Import Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Analyses the supporting documents of a student folder and returns their status.
     *
     * Delegates entirely to the repository and returns a structured array containing
     * the lists of missing and present document keys, as well as a per-document status map.
     *
     * @param string $numetu Student number identifying the folder
     * @return array{manquants: array<int, string>, presents: array<int, string>, statuts: array<string, string>}
     */
    public function analyserDocuments(string $numetu): array
    {
        return $this->dossierRepo->analyserDocuments($numetu);
    }

    /**
     * Saves the admin's validation decisions for a set of supporting documents.
     *
     * Persists the per-document status map alongside an optional deadline date
     * and a global admin comment for the folder.
     *
     * @param string                $numetu           Student number identifying the folder
     * @param array<string, string> $statutsDocuments Map of document key to validation status
     * @param string|null           $dateLimite       Optional deadline date (format expected by the repository)
     * @param string|null           $commentaire      Optional global admin comment
     * @return bool True on success, false on failure
     */
    public function enregistrerValidation(
        string $numetu,
        array $statutsDocuments,
        ?string $dateLimite = null,
        ?string $commentaire = null
    ): bool {
        return $this->dossierRepo->enregistrerValidation($numetu, $statutsDocuments, $dateLimite, $commentaire);
    }
}