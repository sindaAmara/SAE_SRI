<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Database.php';

use Service\CronReminderService;
use Model\Persistence\FolderRepositoryPDO;
use Model\Persistence\RelanceRepositoryPDO;

define('DAYS_BEFORE_RELAY', 3);

$dryRun = in_array('--dry-run', $argv ?? [], true);

$service = new CronReminderService(
    new FolderRepositoryPDO(),
    new RelanceRepositoryPDO()
);

$service->run($dryRun, DAYS_BEFORE_RELAY);
