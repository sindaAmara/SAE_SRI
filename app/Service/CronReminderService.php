<?php

namespace Service;

use Model\Repository\FolderRepositoryInterface;
use Model\Repository\RelanceRepositoryInterface;
use Service\Email\EmailReminderService;

/**
 * Service used by cron jobs to automatically remind students with incomplete folders.
 */
class CronReminderService
{
    /**
     * @param FolderRepositoryInterface $folderRepo
     * @param RelanceRepositoryInterface $relanceRepo
     */
    public function __construct(
        private FolderRepositoryInterface $folderRepo,
        private RelanceRepositoryInterface $relanceRepo
    ) {}

    /**
     * Iterates through incomplete folders and sends a reminder if none were sent recently.
     * * @param bool $dryRun If true, only echoes targeted emails without sending.
     * @param int $daysBeforeRelay Interval in days between two automatic reminders.
     * @return void
     */
    public function run(bool $dryRun, int $daysBeforeRelay): void
    {
        $folders = $this->folderRepo->findIncompleteFolders();

        foreach ($folders as $folder) {

            $numEtu = isset($folder['NumEtu']) ? strval($folder['NumEtu']) : '';
            $emailAmu = isset($folder['EmailAMU']) ? strval($folder['EmailAMU']) : '';
            $emailPerso = isset($folder['EmailPersonnel']) ? strval($folder['EmailPersonnel']) : '';
            
            // Priority to AMU email, fallback to personal
            $email = $emailAmu !== '' ? $emailAmu : $emailPerso;

            if ($email === '' || $numEtu === '') {
                continue;
            }

            if ($this->relanceRepo->wasRecentlySent($numEtu, $daysBeforeRelay)) {
                continue;
            }

            if ($dryRun) {
                echo "Dry-run → {$email}\n";
                continue;
            }

            $prenom = isset($folder['Prenom']) ? strval($folder['Prenom']) : '';
            $nom = isset($folder['Nom']) ? strval($folder['Nom']) : '';
            $studentName = trim($prenom . ' ' . $nom);

            $sent = EmailReminderService::sendRelance(
                $email,
                $numEtu,
                $studentName,
                []
            );

            if ($sent) {
                $this->relanceRepo->save(
                    $numEtu,
                    "Relance automatique envoyée à {$email}"
                );
            }
        }
    }
}