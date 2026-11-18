<?php

namespace Service\Bob;

use DateTimeImmutable;
use Throwable;

/**
 * FolderContextFormatter
 *
 * Turns a row of the `dossiers` table (as returned by
 * FolderRepositoryInterface::findByNumEtu()) into plain text the model can read.
 * Pure class: no database, no HTTP — easy to unit test.
 */
class FolderContextFormatter
{
    /** @var array<string, string> Workflow status labels */
    private const STATUS_LABELS = [
        'depot'       => "en cours de dépôt (le dossier n'est pas encore complet ou pas encore examiné)",
        'instruction' => "en cours d'instruction (le dossier est en train d'être examiné)",
        'accepte'     => 'accepté',
        'refuse'      => 'refusé',
    ];

    /** @var array<string, string> Per-document status labels */
    private const DOCUMENT_LABELS = [
        'accepted' => 'validé',
        'refused'  => 'refusé, à corriger et redéposer',
        'pending'  => 'déposé, en attente de vérification',
    ];

    /** @var array<string, string> Department head opinion labels */
    private const AVIS_LABELS = [
        'accepte' => 'favorable',
        'refuse'  => 'défavorable',
    ];

    /** @var array<string, string> Mobility type labels */
    private const MOBILITE_LABELS = [
        'stage' => 'stage',
        'etude' => "mobilité d'études",
    ];

    /**
     * Formats the student's folder.
     *
     * @param array<string, mixed>|null $folder Folder row, or null when the student has none
     * @param DateTimeImmutable         $today  Reference date used to compute days left
     * @return string
     */
    public function format(?array $folder, DateTimeImmutable $today): string
    {
        if ($folder === null) {
            return "Aucun dossier de mobilité n'est encore enregistré pour cet étudiant sur la plateforme. "
                . "S'il pense en avoir un, il doit vérifier son inscription sur MoveOn ou contacter le SRI.";
        }

        $status   = $this->str($folder, 'status');
        $mobilite = $this->str($folder, 'Mobilite');

        $mobiliteLabel = self::MOBILITE_LABELS[$mobilite] ?? ($mobilite !== '' ? $mobilite : 'non renseigné');

        $lines = [
            'Type de mobilité : ' . $mobiliteLabel,
            'Destination : ' . $this->destination($folder),
            'Statut du dossier : ' . (self::STATUS_LABELS[$status] ?? ($status !== '' ? $status : 'inconnu')),
        ];

        $dateLimite = $this->str($folder, 'DateLimite');
        if ($dateLimite !== '') {
            $lines[] = 'Date limite : ' . $this->deadline($dateLimite, $status, $today);
        }

        $avis    = $this->str($folder, 'avis_chef_departement');
        $lines[] = 'Avis du chef de département : ' . (self::AVIS_LABELS[$avis] ?? 'pas encore donné');

        if (array_key_exists('valide_admin', $folder)) {
            $valide  = !empty($folder['valide_admin']) ? 'oui' : 'pas encore';
            $lines[] = "Validation finale par l'administration : " . $valide;
        }

        array_push($lines, ...$this->documents($this->str($folder, 'StatutDocuments')));

        $commentaire = $this->str($folder, 'CommentaireAdmin');
        if ($commentaire !== '') {
            $lines[] = "Commentaire de l'administration : " . $commentaire;
        }

        $modifieLe = $this->str($folder, 'ModifieLe');
        if ($modifieLe !== '') {
            $lines[] = 'Dernière mise à jour : ' . $modifieLe;
        }

        return implode("\n", $lines);
    }

    /**
     * Builds "University (Country, Continent)" from the available columns.
     *
     * @param array<string, mixed> $folder
     * @return string
     */
    private function destination(array $folder): string
    {
        $university = $this->str($folder, 'Destination');
        $details    = array_filter([$this->str($folder, 'Pays'), $this->str($folder, 'Continent')]);

        if ($university === '' && $details === []) {
            return 'non renseignée';
        }
        if ($university === '') {
            return implode(', ', $details);
        }
        return $details === [] ? $university : $university . ' (' . implode(', ', $details) . ')';
    }

    /**
     * Formats the deadline with the number of days left (or overdue).
     *
     * @param string            $raw    Raw date from the database
     * @param string            $status Folder status
     * @param DateTimeImmutable $today  Reference date
     * @return string
     */
    private function deadline(string $raw, string $status, DateTimeImmutable $today): string
    {
        try {
            $limit = new DateTimeImmutable($raw);
        } catch (Throwable) {
            return $raw;
        }

        $formatted = $limit->format('d/m/Y');

        // No need to alarm the student if the folder is already accepted
        if ($status === 'accepte') {
            return $formatted;
        }

        $days = (int) $today->diff($limit)->format('%r%a');
        return $days >= 0
            ? "$formatted (il reste $days jour(s))"
            : "$formatted (date dépassée depuis " . abs($days) . ' jour(s))';
    }

    /**
     * Lists each document with its validation status.
     *
     * @param string $json StatutDocuments JSON, e.g. {"cv":"refused","photo":"pending"}
     * @return array<int, string>
     */
    private function documents(string $json): array
    {
        $documents = json_decode($json, true);
        if (!is_array($documents) || $documents === []) {
            return [];
        }

        $lines = ['Documents :'];
        foreach ($documents as $name => $state) {
            $label   = is_string($state) ? (self::DOCUMENT_LABELS[$state] ?? $state) : 'inconnu';
            $lines[] = '  ' . $this->documentName((string) $name) . ' : ' . $label;
        }
        return $lines;
    }

    /**
     * "cv" -> "CV", "lettre_motivation" -> "Lettre motivation".
     *
     * @param string $key Document key
     * @return string
     */
    private function documentName(string $key): string
    {
        return strlen($key) <= 3 ? strtoupper($key) : ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Reads a column as a trimmed string ('' when missing or null).
     *
     * @param array<string, mixed> $folder
     * @param string               $key
     * @return string
     */
    private function str(array $folder, string $key): string
    {
        $value = $folder[$key] ?? '';
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
