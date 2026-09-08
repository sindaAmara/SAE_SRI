<?php
/**
 * Partial: Chatbot / Assistant Widget
 *
 * Renders the two root elements required by chatbot.js:
 *
 * - #help-bubble   : the floating trigger button (💬 emoji) that opens the
 *                    chat popup when clicked.
 * - #help-popup    : the chat panel (.chat-popup), composed of:
 *     - .help-popup-header  : title ("Assistant" / translated) + close button (✖).
 *     - #chat-messages      : scrollable message thread; populated by chatbot.js.
 *     - #quick-actions      : quick-reply chip area; populated by chatbot.js.
 *
 * This partial is included by base.php (for admin and student roles) and
 * base_home.php (always). It is intentionally excluded from base.php when
 * $userRole is 'coordinateur', 'coordinateur_etude', 'coordinateur_stage', or
 * 'chef_departement', and is never included by base_superadmin.php or
 * base_minimal.php.
 *
 * All dynamic content (messages, quick actions) is injected at runtime by
 * js/chatbot.js via the Anthropic API. The markup here is purely structural.
 *
 * @var Closure(array<string, string>): string $t Translation callable — used for the "Assistant" header label
 */
?>
<div id="help-bubble">💬</div>

<div id="help-popup" class="chat-popup">
    <div class="help-popup-header">
        <span><?= $t(['fr' => 'Assistant', 'en' => 'Assistant']) ?></span>
        <button>✖</button>
    </div>
    <div id="chat-messages" class="chat-messages"></div>
    <div id="quick-actions" class="quick-actions"></div>
</div>