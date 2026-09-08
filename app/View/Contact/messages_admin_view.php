<?php
/**
 * View: Admin Conversation Detail
 *
 * Displays the full thread of a single student support conversation and
 * provides an admin reply form and a delete action.
 *
 * ── HEADER ───────────────────────────────────────────────────────────────────
 * - Back link: returns to the inbox list (index.php?page=messages-admin).
 * - Delete form: a POST form with action=delete and the conversation id as a
 *   hidden field. A JS confirm() prompt is shown before submission.
 *
 * ── CONVERSATION METADATA ────────────────────────────────────────────────────
 * Three info rows:
 * - Student: full name + student number from getStudentNumEtu().
 * - Email: clickable mailto: link.
 * - Subject: translated label from the local $subjects map, falling back to the
 *   raw subject key stored on the conversation.
 *
 * ── MESSAGE THREAD (.chat-history) ───────────────────────────────────────────
 * All messages from $conversation->getMessages() are rendered in order.
 * Admin messages (getSenderType() === 'admin') receive the class
 * chat-message--admin; student messages receive chat-message--student.
 * The sender label for admin messages is "Vous (Service RI)" / "You (IR Office)";
 * for student messages it is the student's name from getName().
 * Each message timestamp is formatted as d/m/Y H:i.
 * Content is sanitised with nl2br() + htmlspecialchars().
 *
 * ── REPLY FORM ───────────────────────────────────────────────────────────────
 * POSTs to index.php?page=messages-admin&action=respond&id={id}&lang={lang}.
 * A single required textarea (name="response") with a submit button.
 * No client-side validation beyond the HTML required attribute.
 *
 * $subjects is built locally (identical to the list view) so the subject
 * label resolves correctly without an extra variable from the controller.
 *
 * A hidden #app-config div carries data-lang and data-role="admin".
 *
 * The page title is built as "$t('Ticket de / Ticket from') {name}".
 * The rendered HTML is passed to the base layout with styles (messages_admin.css),
 * no scripts, $activeMenu = 'messages', $userRole = 'admin'.
 * Note: $noMain is NOT set here (unlike the list view), so the standard
 * <main> wrapper is used.
 *
 * @var callable                       $t            Translation callable — accepts ['fr' => '...', 'en' => '...']
 * @var string                         $lang         Current language code (e.g. 'fr' or 'en')
 * @var \Model\Entity\Conversation     $conversation The conversation entity to display; assumed non-null (controller guards this)
 */
ob_start();

$subjects = [
    'mobility'  => $t(['fr' => 'Question sur ma mobilité', 'en' => 'Mobility question']),
    'documents' => $t(['fr' => 'Documents requis', 'en' => 'Required documents']),
    'partners'  => $t(['fr' => 'Universités partenaires', 'en' => 'Partner universities']),
    'technical' => $t(['fr' => 'Problème technique', 'en' => 'Technical issue']),
    'other'     => $t(['fr' => 'Autre', 'en' => 'Other'])
];
?>

<div class="message-view-container">
    <div class="message-view-header">
        <a href="index.php?page=messages-admin&lang=<?= $lang ?>" class="back-btn">
            ← <?= $t(['fr' => 'Retour', 'en' => 'Back']) ?>
        </a>

        <form method="POST" action="index.php?page=messages-admin&lang=<?= $lang ?>" class="inline-form">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $conversation->getId() ?>"> 
            <button type="submit" class="delete-btn"
                    onclick="return confirm('<?= $t(['fr' => 'Supprimer ?', 'en' => 'Delete?']) ?>')">
                🗑️ <?= $t(['fr' => 'Supprimer', 'en' => 'Delete']) ?>
            </button>
        </form>
    </div>

    <div class="message-detail">
        <div class="message-info">
            <div class="info-row">
                <span class="info-label"><?= $t(['fr' => 'Étudiant :', 'en' => 'Student:']) ?></span>
                <span class="info-value">
                    <?= htmlspecialchars($conversation->getName()) ?>
                    <small>(<?= htmlspecialchars($conversation->getStudentNumEtu()) ?>)</small>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t(['fr' => 'Email :', 'en' => 'Email:']) ?></span>
                <span class="info-value">
                    <a href="mailto:<?= htmlspecialchars($conversation->getEmail()) ?>">
                        <?= htmlspecialchars($conversation->getEmail()) ?>
                    </a>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label"><?= $t(['fr' => 'Sujet :', 'en' => 'Subject:']) ?></span>
                <span class="info-value">
                    <?= htmlspecialchars($subjects[$conversation->getSubject()] ?? $conversation->getSubject()) ?>
                </span>
            </div>
        </div>

        <div class="message-content">
            <h3><?= $t(['fr' => 'Fil de discussion', 'en' => 'Discussion thread']) ?></h3>
            
            <div class="chat-history">
                <?php foreach ($conversation->getMessages() as $msg): ?>
                    <?php $isAdmin = $msg->getSenderType() === 'admin'; ?>
                    
                    <div class="chat-message <?= $isAdmin ? 'chat-message--admin' : 'chat-message--student' ?>">
                        <div class="chat-message-meta">
                            <strong><?= $isAdmin ? $t(['fr' => 'Vous (Service RI)', 'en' => 'You (IR Office)']) : htmlspecialchars($conversation->getName()) ?></strong>
                            <span class="chat-message-date"><?= $msg->getCreatedAt()->format('d/m/Y H:i') ?></span>
                        </div>
                        <div class="chat-message-text">
                            <?= nl2br(htmlspecialchars($msg->getContent())) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-response-form">
            <h3><?= $t(['fr' => 'Répondre', 'en' => 'Reply']) ?></h3>

            <form method="POST" action="index.php?page=messages-admin&action=respond&id=<?= $conversation->getId() ?>&lang=<?= $lang ?>">
                <textarea
                    name="response"
                    rows="4"
                    required
                    placeholder="<?= $t(['fr' => 'Écrivez votre réponse ici...', 'en' => 'Type your reply here...']) ?>"
                ></textarea>

                <button type="submit" class="btn-submit">
                    <?= $t(['fr' => 'Envoyer', 'en' => 'Send']) ?>
                </button>
            </form>
        </div>
    </div>
</div>

<div id="app-config"
     data-lang="<?= htmlspecialchars($lang) ?>"
     data-role="admin"
     style="display:none;">
</div>
<?php
$content = ob_get_clean();
$title = $t(['fr' => 'Ticket de', 'en' => 'Ticket from']) . ' ' . $conversation->getName();
$styles = ['styles/messages_admin.css'];
$scripts = [];
$activeMenu = 'messages';
$userRole = 'admin';

include __DIR__ . '/../Layout/base.php';