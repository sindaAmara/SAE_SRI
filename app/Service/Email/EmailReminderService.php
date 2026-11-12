<?php

namespace Service\Email;

use Mailjet\Client;
use Mailjet\Resources;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Service handling email communications via PHPMailer.
 * Provides templates and methods for reminders, update notifications, and message alerts.
 */
class EmailReminderService
{
    /** @var string Sender email address */
    private static string $fromEmail = 'relance-iut-amu@ri-amu.app';

    /** @var string Sender display name */
    private static string $fromName = 'IUT Aix - Gestion Dossiers';

    /** @var string Logo URL used in all email templates */
    private static string $logoUrl = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRBl1mF7ktLaJxYCRD64rZyUJ1WcUDvcJBcIw&s';

    /**
     * Create and return a PHPMailer instance.
     * * @return ClientcreateMailerInstance
     * @throws \RuntimeException if credentials (API Key or Secret) are missing in environment variables.
     */
    private static function createMailerInstance(): PHPMailer
    {
        $mail = new PHPMailer();
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 465;
        $mail->CharSet = 'UTF-8';
        return $mail;
    }

    /**
     * Render an email template, inject variables, and apply inline CSS.
     * * @param string $template Template filename (without .php).
     * @param array<string, mixed> $data Variables to extract into the template scope.
     * @return string Rendered HTML content with inlined styles.
     */
    private static function renderEmailTemplate(string $template, array $data = []): string
    {
        extract($data);
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);
        $file = $root . '/app/View/Email/' . $template . '.php';

        if (!file_exists($file)) {
            error_log("❌ Email template not found: {$file}");
            return '';
        }

        ob_start();
        require $file;
        $html = ob_get_clean() ?: '';

        $cssFile = $root . '/public/styles/emails.css';
        if (file_exists($cssFile)) {
            $css    = file_get_contents($cssFile) ?: '';
            $html   = (new CssToInlineStyles())->convert($html, $css);
        } else {
            error_log("⚠️ emails.css not found: {$cssFile}");
        }

        return $html;
    }

    /**
     * Send a generic email using PHPMailer.
     * * @param PHPMailer $mail PHPMailer instance.
     * @param string $toEmail Recipient email.
     * @param string $name Recipient name
     * @param string $subject Email subject.
     * @param string $htmlMessage Email message in html format.
     * @return bool True if the email was successfully sent, false otherwise.
     * @throws Exception for any PHPMailer errors while sending the email.
     */
    private static function sendEmail(
        PHPMailer $mail,
        string $toEmail,
        string $name,
        string $subject,
        string $htmlMessage
    ): bool {
        $mail->setFrom(self::$fromEmail, self::$fromName);
        $mail->addAddress($toEmail, $name);
        $mail->Subject = $subject;
        $mail->msgHTML($htmlMessage);
        $mail->isHTML(true);
        $mail->AltBody = strip_tags($htmlMessage);

        return $mail->send();
    }

    /**
     * Sends a reminder (relance) to a student about an incomplete folder.
     * * @param string $toEmail Recipient email.
     * @param int|string $dossierId Folder ID or student number.
     * @param string $studentName Student's display name.
     * @param array<string> $itemsToComplete List of missing documents or actions.
     * @return bool True if the email was accepted by Mailjet, false otherwise.
     */
    public static function sendRelance(
        string $toEmail,
        int|string $dossierId,
        string $studentName = '',
        array $itemsToComplete = []
    ): bool {
        try {
            $mail = self::createMailerInstance();

            $subject = "Rappel : Folder incomplet (ID {$dossierId})";

            $htmlMessage = self::renderEmailTemplate('relance', [
                'logoUrl'          => self::$logoUrl,
                'studentName'      => trim($studentName ?: ''),
                'dossierId'        => $dossierId,
                'itemsToComplete'  => $itemsToComplete,
                'folderLink'       => "https://amara.alwaysdata.net/index.php?page=folders-student&action=view&id=" . urlencode((string)$dossierId)
            ]);

            $response = self::sendEmail($mail, $toEmail, $studentName ?: '', $subject, $htmlMessage);

            if ($response) {
                error_log("✅ Email successfully sent to {$toEmail} via Mailjet");
                return true;
            } else {
                error_log("❌ Mailjet error: " . json_encode($mail->ErrorInfo));
                return false;
            }
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a notification when a folder's content or status is updated.
     *
     * @param string $toEmail Student's email.
     * @param string $studentName Student's name.
     * @param string $numEtu Student ID.
     * @param array<int, string> $updates List of update strings containing section markers.
     * @return bool
     */
    public static function sendFolderUpdateNotification(
        string $toEmail,
        string $studentName,
        string $numEtu,
        array $updates
    ): bool {
        if (empty($updates)) {
            return true;
        }

        try {
            $mail = self::createMailerInstance();

            $subject = "Mise à jour de votre dossier RI (ID {$numEtu})";

            $sectionAcceptees = '';
            $sectionRefusees  = '';
            $statutGlobal     = '';
            $dateLimite       = '';
            $autresLignes     = [];

            foreach ($updates as $upd) {
                if (str_starts_with($upd, '__SECTION_ACCEPTEES__')) {
                    $sectionAcceptees = (string) str_replace(['__SECTION_ACCEPTEES__', '__END_SECTION__'], '', $upd);
                } elseif (str_starts_with($upd, '__SECTION_REFUSEES__')) {
                    $sectionRefusees = (string) str_replace(['__SECTION_REFUSEES__', '__END_SECTION__'], '', $upd);
                } elseif (str_starts_with($upd, '__STATUT_GLOBAL__')) {
                    $statutGlobal = (string) str_replace(['__STATUT_GLOBAL__', '__END_STATUT__'], '', $upd);
                } elseif (str_starts_with($upd, '__DATE_LIMITE__')) {
                    $dateLimite = (string) str_replace(['__DATE_LIMITE__', '__END_DATE__'], '', $upd);
                } else {
                    $autresLignes[] = $upd;
                }
            }

            $htmlMessage = self::renderEmailTemplate('folder_update', [
                'logoUrl'          => self::$logoUrl,
                'studentName'      => trim($studentName),
                'sectionAcceptees' => $sectionAcceptees,
                'sectionRefusees'  => $sectionRefusees,
                'statutGlobal'     => $statutGlobal,
                'dateLimite'       => $dateLimite,
                'autresLignes'     => $autresLignes
            ]);

            $response = self::sendEmail($mail, $toEmail, $studentName ?: '', $subject, $htmlMessage);

            if ($response) {
                error_log("✅ Folder update notification sent to {$toEmail} via Mailjet");
                return true;
            }

            error_log("❌ Mail error: " . json_encode($mail->ErrorInfo));
            return false;
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a confirmation email when all submitted documents are validated.
     *
     * @param string $toEmail Recipient email.
     * @param string $studentName Student's name.
     * @param array<string> $validatedDocuments List of document keys (photo, cv, etc.).
     * @param string $numEtu Student ID.
     * @return bool
     */
    public static function sendValidationConfirmation(
        string $toEmail,
        string $studentName,
        array $validatedDocuments,
        string $numEtu
    ): bool {
        try {
            $mail = self::createMailerInstance();

            $subject = "Documents Validés - Folder #{$numEtu}";

            $documentLabels = [
                'photo'             => 'Photo',
                'cv'                => 'CV',
                'convention'        => 'Convention de Stage',
                'lettre_motivation' => 'Lettre de Motivation',
                'langues_file'      => 'Attestation de Langues'
            ];
            $mappedDocs = array_map(fn($doc) => $documentLabels[$doc] ?? ucfirst($doc), $validatedDocuments);

            $htmlMessage = self::renderEmailTemplate('validation_complete', [
                'logoUrl'            => self::$logoUrl,
                'studentName'        => trim($studentName),
                'validatedDocuments' => $mappedDocs,
                'numEtu'             => $numEtu,
                'folderLink'         => "https://amara.alwaysdata.net/index.php?page=folders-student&action=view&numetu=" . urlencode($numEtu)
            ]);

            $response = self::sendEmail($mail, $toEmail, $studentName ?: '', $subject, $htmlMessage);

            if ($response) {
                error_log("✅ Validation email successfully sent to {$toEmail}");
                return true;
            } else {
                error_log("❌ Mailjet error: " . json_encode($mail->ErrorInfo));
                return false;
            }
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends an email confirming that a document has been successfully deposited.
     *
     * @param string $toEmail Student email.
     * @param string $studentName Student name.
     * @param string $documentType Key representing the document type.
     * @param string $numEtu Student ID.
     * @return bool
     */
    public static function sendDocumentDeposited(
        string $toEmail,
        string $studentName,
        string $documentType,
        string $numEtu
    ): bool {
        try {
            $mail = self::createMailerInstance();

            $documentLabels = [
                'photo'             => 'Photo',
                'cv'                => 'CV',
                'convention'        => 'Convention de Stage',
                'lettre_motivation' => 'Lettre de Motivation',
                'langues_file'      => 'Attestation de Langues'
            ];
            $docLabel = $documentLabels[$documentType] ?? ucfirst($documentType);

            $subject     = "Document déposé - {$docLabel}";
            $htmlMessage = self::renderEmailTemplate('document_deposited', [
                'logoUrl'       => self::$logoUrl,
                'studentName'   => trim($studentName),
                'documentLabel' => $docLabel,
                'folderLink'    => "https://amara.alwaysdata.net/index.php?page=folders-student&action=view&numetu=" . urlencode($numEtu)
            ]);

            $response = self::sendEmail($mail, $toEmail, $studentName ?: '', $subject, $htmlMessage);

            if ($response) {
                error_log("✅ Document deposit email sent to {$toEmail} for {$docLabel}");
                return true;
            }

            error_log("❌ Mailjet error: " . json_encode($mail->ErrorInfo));
            return false;
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends an email notifying the student that a specific document has been validated.
     *
     * @param string $toEmail Student email.
     * @param string $studentName Student name.
     * @param string $documentType Document type key.
     * @param string $numEtu Student ID.
     * @return bool
     */
    public static function sendDocumentValidated(
        string $toEmail,
        string $studentName,
        string $documentType,
        string $numEtu
    ): bool {
        try {
            $mail = self::createMailerInstance();

            $documentLabels = [
                'photo'             => 'Photo',
                'cv'                => 'CV',
                'convention'        => 'Convention de Stage',
                'lettre_motivation' => 'Lettre de Motivation',
                'langues_file'      => 'Attestation de Langues'
            ];
            $docLabel = $documentLabels[$documentType] ?? ucfirst($documentType);

            $subject     = "Document validé ✓ - {$docLabel}";
            $htmlMessage = self::renderEmailTemplate('document_validated', [
                'logoUrl'       => self::$logoUrl,
                'studentName'   => trim($studentName),
                'documentLabel' => $docLabel,
                'folderLink'    => "https://amara.alwaysdata.net/index.php?page=folders-student&action=view&numetu=" . urlencode($numEtu)
            ]);

            $response = self::sendEmail($mail, $toEmail, $studentName ?: '', $subject, $htmlMessage);

            if ($response) {
                error_log("✅ Document validation email sent to {$toEmail} for {$docLabel}");
                return true;
            }

            error_log("❌ Mailjet error: " . json_encode($mail->ErrorInfo));
            return false;
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends a notification when a new message is received on the platform.
     * * @param string $toEmail Recipient email.
     * @param string $recipientName Recipient name.
     * @param string $senderName Sender name.
     * @param string $messagePreview Short preview of the message content.
     * @param string $platformLink Absolute URL to the message on the platform.
     * @return bool
     */
    public static function sendMessageNotification(
        string $toEmail,
        string $recipientName,
        string $senderName,
        string $messagePreview,
        string $platformLink
    ): bool {
        try {
            $mail = self::createMailerInstance();

            $subject = "Nouveau message de {$senderName}";

            $truncatedPreview = strlen($messagePreview) > 150
                ? substr($messagePreview, 0, 150) . '...'
                : $messagePreview;

            $htmlMessage = self::renderEmailTemplate('message_notification', [
                'recipientName'  => trim($recipientName),
                'senderName'     => trim($senderName),
                'messagePreview' => $truncatedPreview,
                'platformLink'   => $platformLink
            ]);

            $response = self::sendEmail($mail, $toEmail, $recipientName ?: '', $subject, $htmlMessage);

            if ($response) {
                error_log("✅ Message notification email sent to {$toEmail} from {$senderName}");
                return true;
            }

            error_log("❌ Mailjet error: " . json_encode($mail->ErrorInfo));
            return false;
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
