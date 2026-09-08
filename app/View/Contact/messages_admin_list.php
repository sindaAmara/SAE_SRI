<?php
/**
 * View: Admin Message Inbox — Outlook-style Split Layout
 *
 * Renders a two-pane Outlook-style shell (.outlook-shell) for browsing all
 * student support conversations.
 *
 * ── TOOLBAR ──────────────────────────────────────────────────────────────────
 * Displays a heading and two filter links:
 * - "All" → index.php?page=messages-admin&filter=all   (total count badge)
 * - "Unread" → index.php?page=messages-admin&filter=unread
 * The active link receives the 'active' CSS class based on $filter.
 *
 * ── LIST PANE (.outlook-list-pane) ───────────────────────────────────────────
 * When $conversations is empty, a "No conversations" notice is shown.
 * Otherwise each conversation is rendered as an <a> (.message-item) linking to
 * the detail view (messages-admin&action=view&id={id}).
 *
 * Item anatomy:
 * - Unread dot (.unread-dot): rendered only when hasUnreadMessagesFor('admin') is true;
 *   the item also gets the 'unread' CSS class.
 * - Selected state: the 'selected' CSS class is added when $conv->getId() matches
 *   the ?id= query parameter ($currentId), which is resolved once at the top.
 * - Top row: sender name (left) + conversation creation date in d/m H:i (right).
 * - Subject: translated label from the $subjects map, falling back to the raw key.
 * - Preview: first 80 characters of the last message content (getLastMessage()),
 *   followed by an ellipsis; empty string when the conversation has no messages.
 *
 * ── READING PANE (.outlook-reading-pane) ─────────────────────────────────────
 * Rendered as a static placeholder ("Select a conversation") in this view.
 * The actual conversation detail is loaded in messages_admin_view.php.
 * The JS on the page (if any) may inject the detail view into this pane via AJAX,
 * but no inline JS or script tags are emitted here.
 *
 * $subjects is built once at the top using $t() for translation consistency.
 * $currentId is read from $_GET['id'] (cast to int) to highlight the selected item
 * when navigating back from the detail view.
 *
 * A hidden #app-config div carries data-lang and data-role="admin".
 *
 * The rendered HTML is passed to the base layout with styles (messages_admin.css),
 * no scripts, $activeMenu = 'messages', $userRole = 'admin', $noMain = true
 * (the layout omits the <main> wrapper so the shell fills the viewport).
 *
 * @var string                             $lang          Current language code (e.g. 'fr' or 'en')
 * @var array<int, \Model\Entity\Conversation> $conversations All conversations to display (already filtered by $filter before being passed in)
 * @var string                             $filter        Active filter key: 'all' or 'unread'
 * @var Closure                            $t             Translation callable — accepts ['fr' => '...', 'en' => '...']
 */

ob_start();

$subjects = [
    'mobility'  => $t(['fr' => 'Question sur ma mobilité',  'en' => 'Mobility question']),
    'documents' => $t(['fr' => 'Documents requis',          'en' => 'Required documents']),
    'partners'  => $t(['fr' => 'Universités partenaires',   'en' => 'Partner universities']),
    'technical' => $t(['fr' => 'Problème technique',        'en' => 'Technical issue']),
    'other'     => $t(['fr' => 'Autre',                     'en' => 'Other']),
];

// On récupère l'ID de la conversation ouverte dans l'URL pour gérer l'état "sélectionné"
$currentId = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>

<div class="outlook-shell">
    <div class="outlook-toolbar">
        <h1>📬 <?= $t(['fr' => 'Demandes des étudiants', 'en' => 'Student Requests']) ?></h1>

        <a href="index.php?page=messages-admin&filter=all&lang=<?= $lang ?>"
           class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
            <?= $t(['fr' => 'Toutes', 'en' => 'All']) ?> (<?= count($conversations) ?>)
        </a>
        <a href="index.php?page=messages-admin&filter=unread&lang=<?= $lang ?>"
           class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">
            <?= $t(['fr' => 'Non lues', 'en' => 'Unread']) ?>
        </a>
    </div>

    <div class="outlook-panes">
        <div class="outlook-list-pane" id="msgList">
            <?php if (empty($conversations)): ?>
                <div class="no-messages">
                    <?= $t(['fr' => 'Aucune conversation.', 'en' => 'No conversations.']) ?>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <?php
                    $isUnread = $conv->hasUnreadMessagesFor('admin');
                    $isSelected = ($currentId === $conv->getId());
                    $subjectLabel = htmlspecialchars($subjects[$conv->getSubject()] ?? $conv->getSubject());
                    $lastMsg = $conv->getLastMessage();
                    $preview = $lastMsg ? htmlspecialchars(mb_substr($lastMsg->getContent(), 0, 80)) : '';
                    ?>
                    
                    <a href="index.php?page=messages-admin&action=view&id=<?= $conv->getId() ?>&lang=<?= $lang ?>" 
                       class="message-item <?= $isUnread ? 'unread' : '' ?> <?= $isSelected ? 'selected' : '' ?>"
                       style="text-decoration: none !important; outline: none !important;">
                        
                        <?php if ($isUnread): ?>
                            <div class="unread-dot"></div>
                        <?php endif; ?>

                        <div class="msg-item-top">
                            <span class="msg-item-name"><?= htmlspecialchars($conv->getName()) ?></span>
                            <span class="msg-item-date"><?= $conv->getCreatedAt()->format('d/m H:i') ?></span>
                        </div>
                        <div class="msg-item-subject"><?= $subjectLabel ?></div>
                        <div class="msg-item-preview"><?= $preview ?>…</div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="outlook-reading-pane" id="readingPane">
            <div class="reading-empty">
                <div class="reading-empty-icon">✉️</div>
                <span><?= $t(['fr' => 'Sélectionnez une conversation', 'en' => 'Select a conversation']) ?></span>
            </div>
        </div>
    </div>
</div>

<div id="app-config"
     data-lang="<?= htmlspecialchars($lang) ?>"
     data-role="admin"
     style="display:none;">
</div>

<?php
$content    = ob_get_clean();
$title      = $t(['fr' => 'Messages', 'en' => 'Messages']);
$styles     = ['styles/messages_admin.css'];
$scripts    = []; 
$activeMenu = 'messages';
$userRole   = 'admin';
$noMain     = true;

include __DIR__ . '/../Layout/base.php';