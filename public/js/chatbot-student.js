/**
 * Bob chatbot (student interface)
 *
 * 3 classes, each with a single responsibility:
 *   ChatStorage — local persistence of the conversation (localStorage)
 *   BobApi      — server calls (index.php?page=api/bob)
 *   BobChatbot  — rendering and user interactions (DOM)
 *
 * Uses existing CSS classes: chat-popup, chat-messages, chat-input-area,
 * quick-actions, bot-message, user-message, help-popup-header
 */

/* ───────────────────────────── ChatStorage ───────────────────────────── */

class ChatStorage {
    static #PREFIX = 'bob_chat_';

    /**
     * @param {string} sessionToken Token regenerated at each login (provided by PHP).
     *   If it differs from the stored one, this is a new login:
     *   the previous conversation is cleared.
     */
    constructor(sessionToken) {
        const isNewLogin = sessionToken && this.#get('session') !== sessionToken;

        // New login or page reload (F5): start from an empty conversation.
        // Normal navigation between pages keeps the conversation.
        if (isNewLogin || ChatStorage.#isPageReload()) {
            this.clear();
        }
        if (sessionToken) {
            this.#set('session', sessionToken);
        }
    }

    /**
     * Tells whether the current page was reloaded (F5 / refresh button).
     * @returns {boolean} true on reload, false on normal navigation
     */
    static #isPageReload() {
        try {
            const nav = performance.getEntriesByType('navigation')[0];
            return nav ? nav.type === 'reload' : performance.navigation?.type === 1;
        } catch (e) {
            return false;
        }
    }

    loadHistory() {
        try {
            const parsed = JSON.parse(this.#get('history'));
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    saveHistory(history) {
        this.#set('history', JSON.stringify(history));
    }

    isPopupOpen() {
        return this.#get('open') === '1';
    }

    setPopupOpen(isOpen) {
        this.#set('open', isOpen ? '1' : '0');
    }

    clear() {
        try {
            Object.keys(localStorage)
                .filter(k => k.startsWith(ChatStorage.#PREFIX))
                .forEach(k => localStorage.removeItem(k));
        } catch (e) {}
    }

    #get(key) {
        try { return localStorage.getItem(ChatStorage.#PREFIX + key); } catch (e) { return null; }
    }

    #set(key, value) {
        try { localStorage.setItem(ChatStorage.#PREFIX + key, value); } catch (e) {}
    }
}

/* ─────────────────────────────── BobApi ─────────────────────────────── */

class BobApi {
    constructor(endpoint = 'index.php?page=api/bob') {
        this.endpoint = endpoint;
    }

    /**
     * Sends the conversation history to the server and returns Bob's reply.
     * The system prompt and the student's folder are added server-side.
     * @param {Array<{role: string, content: string}>} history
     * @returns {Promise<string>} Bob's reply
     * @throws {Error} When the server answers with an error
     */
    async send(history) {
        const res = await fetch(this.endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ messages: history })
        });
        if (!res.ok) {
            // Read the server's error message so it shows up in the console
            let detail = '';
            try { detail = (await res.json()).error ?? ''; } catch (e) {}
            throw new Error(`HTTP ${res.status}${detail ? ' — ' + detail : ''}`);
        }

        const data = await res.json();
        if (data.error) throw new Error(data.error);
        return data.reply;
    }
}

/* ───────────────────────────── BobChatbot ───────────────────────────── */

class BobChatbot {
    static #TEXTS = {
        welcome: {
            fr: "Salut ! Je suis <b>Bob</b> 👋 Je suis là pour t'aider avec ta mobilité internationale. Pose-moi n'importe quelle question sur ton dossier, les partenaires ou la plateforme !",
            en: "Hey! I'm <b>Bob</b> 👋 I'm here to help with your international mobility. Ask me anything about your folder, partner universities, or the platform!"
        },
        error: {
            fr: "Désolé, je rencontre un problème technique. Réessaie dans quelques instants 🙏",
            en: "Sorry, I'm having a technical issue. Please try again in a moment 🙏"
        },
        quickActions: {
            fr: ['📁 Mon dossier', '🌍 Trouver un partenaire', '📄 Documents requis', '📊 Mon avancement'],
            en: ['📁 My folder',   '🌍 Find a partner',       '📄 Required documents', '📊 My progress']
        }
    };

    /**
     * @param {ChatStorage} storage Conversation persistence
     * @param {BobApi} api           Server client
     * @param {string} lang          Interface language ('fr' or 'en')
     */
    constructor(storage, api, lang = 'fr') {
        this.storage = storage;
        this.api     = api;
        this.lang    = lang in BobChatbot.#TEXTS.welcome ? lang : 'fr';
        this.history = storage.loadHistory();

        this.bubble        = document.getElementById('help-bubble');
        this.popup         = document.querySelector('.chat-popup');
        this.closeBtn      = document.querySelector('.help-popup-header button');
        this.chatContainer = document.querySelector('.chat-messages');
        this.inputField    = document.querySelector('.chat-input-area input');
        this.sendBtn       = document.querySelector('.chat-input-area button');
        this.quickActions  = document.querySelector('.quick-actions');

        this.#bindEvents();
        this.#restore();
    }

    // ── Public actions ───────────────────────────────────────────────────────

    /** Opens or closes the chat popup. */
    toggle() {
        if (!this.popup) return;
        const willOpen = window.getComputedStyle(this.popup).display === 'none';
        this.popup.style.display = willOpen ? 'flex' : 'none';
        this.storage.setPopupOpen(willOpen);

        if (!willOpen) return;
        if (this.history.length === 0 && this.chatContainer?.innerHTML === '') {
            this.#showWelcome();
        }
        requestAnimationFrame(() => this.#scrollToBottom());
    }

    /** Closes the chat popup. */
    close() {
        if (this.popup) this.popup.style.display = 'none';
        this.storage.setPopupOpen(false);
    }

    /** Deletes the stored conversation. */
    clearHistory() {
        this.storage.clear();
        this.history = [];
    }

    // ── Initialisation ───────────────────────────────────────────────────────

    #bindEvents() {
        this.bubble?.addEventListener('click', () => this.toggle());
        this.closeBtn?.addEventListener('click', () => this.close());
        this.sendBtn?.addEventListener('click', () => this.#handleSend());
        this.inputField?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.#handleSend();
            }
        });

        // In addition to the session token: also clear on "Log out" click
        document
            .querySelector('a[href*="logout"], a[href*="deconnexion"], .logout-btn')
            ?.addEventListener('click', () => this.storage.clear());
    }

    #restore() {
        if (!this.chatContainer || !this.popup) return;

        this.history.forEach(({ role, content }) => {
            const isUser = role === 'user';
            this.#addMessage(isUser ? this.#escape(content) : this.#format(content), isUser ? 'user' : 'bot');
        });
        this.#scrollToBottom();

        if (this.storage.isPopupOpen()) {
            this.popup.style.display = 'flex';
            if (this.history.length === 0) this.#showWelcome();
        }
    }

    #showWelcome() {
        this.#addMessage(BobChatbot.#TEXTS.welcome[this.lang], 'bot');
        this.#scrollToBottom();
        this.#renderQuickActions();
    }

    #renderQuickActions() {
        if (!this.quickActions) return;
        this.quickActions.innerHTML = '';

        BobChatbot.#TEXTS.quickActions[this.lang].forEach(label => {
            const btn = document.createElement('button');
            btn.textContent = label;
            btn.addEventListener('click', () => {
                this.quickActions.innerHTML = '';
                this.#sendMessage(label);
            });
            this.quickActions.appendChild(btn);
        });
    }

    // ── Conversation ─────────────────────────────────────────────────────────

    #handleSend() {
        const text = this.inputField?.value.trim();
        if (!text) return;
        this.inputField.value = '';
        this.#sendMessage(text);
    }

    async #sendMessage(text) {
        const userMsgEl = this.#addMessage(this.#escape(text), 'user');
        this.#scrollTo(userMsgEl);

        this.#pushHistory('user', text);
        this.#setLoading(true);

        try {
            const reply = await this.api.send(this.history);
            this.#addMessage(this.#format(reply), 'bot');
            this.#pushHistory('assistant', reply);
        } catch (err) {
            this.#addMessage(BobChatbot.#TEXTS.error[this.lang], 'bot');
            console.error('Bob error:', err);
        } finally {
            this.#setLoading(false);
            // Keep the student's message at the top instead of scrolling to the end of the reply
            this.#scrollTo(userMsgEl);
        }
    }

    #pushHistory(role, content) {
        this.history.push({ role, content });
        this.storage.saveHistory(this.history);
    }

    // ── DOM ──────────────────────────────────────────────────────────────────

    #addMessage(html, sender) {
        if (!this.chatContainer) return null;
        const div = document.createElement('div');
        div.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
        div.innerHTML = html;
        this.chatContainer.appendChild(div);
        return div;
    }

    #setLoading(on) {
        if (on) {
            const typing = document.createElement('div');
            typing.classList.add('message', 'bot-message');
            typing.id = 'bob-typing';
            typing.innerHTML = '<span class="bob-dot"></span>'.repeat(3);
            this.chatContainer?.appendChild(typing);
        } else {
            document.getElementById('bob-typing')?.remove();
        }

        if (this.sendBtn) {
            this.sendBtn.disabled    = on;
            this.sendBtn.textContent = on ? '…' : '➤';
        }
        if (this.inputField) this.inputField.disabled = on;
    }

    #scrollToBottom() {
        if (this.chatContainer) this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
    }

    // Double requestAnimationFrame: the scroll is applied after the browser reflow
    #scrollTo(el) {
        if (!el || !this.chatContainer) return;
        requestAnimationFrame(() => requestAnimationFrame(() => {
            this.chatContainer.scrollTop = el.offsetTop - 10;
        }));
    }

    // ── Text ─────────────────────────────────────────────────────────────────

    #escape(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /** Escapes HTML, strips markdown and emojis, keeps line breaks. */
    #format(text) {
        return this.#escape(text)
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/\*(.*?)\*/g,     '$1')
            .replace(/^#{1,6}\s+/gm,   '')
            .replace(/^-\s+/gm,        '')
            .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{1F1E6}-\u{1F1FF}]/gu, '')
            .replace(/\n/g, '<br>');
    }
}

/* ─────────────────────────────── Bootstrap ─────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {
    const config = document.getElementById('app-config')?.dataset ?? {};
    // Token regenerated at each login (AuthController -> Layout/chatbot.php)
    const token  = document.getElementById('help-popup')?.dataset.chatSession || config.numetu || '';

    window.chatbot = new BobChatbot(new ChatStorage(token), new BobApi(), config.lang ?? 'fr');

    // Backward compatibility with legacy <div onclick="toggleHelpPopup()">
    window.toggleHelpPopup = () => window.chatbot.toggle();
});