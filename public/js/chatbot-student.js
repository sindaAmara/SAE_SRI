/**
 * Chatbot Bob
 *
 * 3 classes, chacune avec un seul rôle :
 *   ChatStorage — mémoire locale de la conversation (localStorage)
 *   BobApi      — appel au serveur (index.php?page=api/bob)
 *   BobChatbot  — affichage et interactions (DOM)
 *
 * Uses existing CSS classes: chat-popup, chat-messages, chat-input-area,
 * quick-actions, bot-message, user-message, help-popup-header
 */

/* ───────────────────────────── ChatStorage ───────────────────────────── */

class ChatStorage {
    static #PREFIX = 'bob_chat_';

    /**
     * @param {string} sessionToken Jeton qui change à chaque connexion (fourni par PHP).
     *   S'il est différent de celui en mémoire, c'est une nouvelle connexion :
     *   on efface l'ancienne conversation.
     */
    constructor(sessionToken) {
        const isNewLogin = sessionToken && this.#get('session') !== sessionToken;

        // Nouvelle connexion ou page rafraîchie (F5) : on repart d'une conversation vide.
        // Une navigation normale entre les pages garde la conversation.
        if (isNewLogin || ChatStorage.#isPageReload()) {
            this.clear();
        }
        if (sessionToken) {
            this.#set('session', sessionToken);
        }
    }

    /** true si la page a été rechargée (F5 / bouton actualiser), false pour une navigation normale. */
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
     * Envoie l'historique au serveur et renvoie la réponse de Bob.
     * Le prompt système et le dossier de l'étudiant sont ajoutés côté serveur.
     */
    async send(history) {
        const res = await fetch(this.endpoint, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ messages: history })
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

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
     * @param {ChatStorage} storage
     * @param {BobApi} api
     * @param {string} lang
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

    // ── Actions publiques ────────────────────────────────────────────────────

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

    close() {
        if (this.popup) this.popup.style.display = 'none';
        this.storage.setPopupOpen(false);
    }

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

        // En plus du jeton de session : on vide aussi au clic sur "Déconnexion"
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
            // On garde le message de l'étudiant en haut plutôt que de descendre en bas de la réponse
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

    // Double requestAnimationFrame : le scroll s'applique après le reflow du navigateur
    #scrollTo(el) {
        if (!el || !this.chatContainer) return;
        requestAnimationFrame(() => requestAnimationFrame(() => {
            this.chatContainer.scrollTop = el.offsetTop - 10;
        }));
    }

    // ── Texte ────────────────────────────────────────────────────────────────

    #escape(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /** Échappe le HTML, retire le markdown et les emojis, garde les retours à la ligne. */
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

/* ─────────────────────────────── Démarrage ─────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {
    const config = document.getElementById('app-config')?.dataset ?? {};
    // Jeton régénéré à chaque connexion (AuthController -> Layout/chatbot.php)
    const token  = document.getElementById('help-popup')?.dataset.chatSession || config.numetu || '';

    window.chatbot = new BobChatbot(new ChatStorage(token), new BobApi(), config.lang ?? 'fr');

    // Compatibilité avec les anciens <div onclick="toggleHelpPopup()">
    window.toggleHelpPopup = () => window.chatbot.toggle();
});