/**
 * Chatbot Bob — IA powered by Claude API
 * Drop-in replacement for the keyword-based chatbot.
 * Uses existing CSS classes: chat-popup, chat-messages, chat-input-area,
 * quick-actions, bot-message, user-message, help-popup-header
 */

class BobChatbot {
    constructor() {
        const configEl      = document.getElementById('app-config');
        this.currentLang    = configEl?.dataset.lang ?? 'fr';
        this.userRole       = configEl?.dataset.role ?? 'student';
        this.history        = this.#loadHistory();
        this.systemPrompt   = this.#buildSystemPrompt();

        // Existing DOM elements — no IDs changed
        this.bubble        = document.getElementById('help-bubble');
        this.popup         = document.querySelector('.chat-popup');
        this.closeBtn      = document.querySelector('.help-popup-header button');
        this.chatContainer = document.querySelector('.chat-messages');
        this.inputField    = document.querySelector('.chat-input-area input');
        this.sendBtn       = document.querySelector('.chat-input-area button');
        this.quickActions  = document.querySelector('.quick-actions');

        this.#initEvents();
        this.#restoreState();
    }

    #storageKey(suffix) {
        return `bob_chat_${suffix}`;
    }

    #store(key, value) {
        try { localStorage.setItem(key, value); } catch(e) {}
    }

    #retrieve(key) {
        try { return localStorage.getItem(key); } catch(e) { return null; }
    }
    #loadHistory() {
        const storedUser  = this.#retrieve(this.#storageKey('user'));
        const currentUser = document.getElementById('app-config')?.dataset.numetu ?? '';


        if (currentUser && storedUser && storedUser !== currentUser) {
            try { localStorage.removeItem(this.#storageKey('history')); } catch(e) {}
            try { localStorage.removeItem(this.#storageKey('open')); } catch(e) {}
        }


        if (currentUser) {
            this.#store(this.#storageKey('user'), currentUser);
        }

        const raw = this.#retrieve(this.#storageKey('history'));
        return raw ? JSON.parse(raw) : [];
    }

    #saveHistory() {
        this.#store(this.#storageKey('history'), JSON.stringify(this.history));
    }

    #savePopupOpen(isOpen) {
        this.#store(this.#storageKey('open'), isOpen ? '1' : '0');
    }

    #loadPopupOpen() {
        return this.#retrieve(this.#storageKey('open')) === '1';
    }


    #restoreState() {
        if (!this.chatContainer || !this.popup) return;

        if (this.history.length > 0) {
            this.history.forEach(entry => {
                const isUser = entry.role === 'user';
                const html = isUser ? this.#escape(entry.content) : this.#md(entry.content);
                this.#addMessage(html, isUser ? 'user' : 'bot');
            });
            this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
        }

        if (this.#loadPopupOpen()) {
            this.popup.style.display = 'flex';
            // Si jamais le popup était ouvert mais qu'aucun historique n'existe
            // (cas rare : storage vidé entre-temps), on affiche l'accueil.
            if (this.history.length === 0) {
                this.#showWelcome();
            }
        }
    }

    // ── System prompt ──────────────────────────────────────────────────────────

    #buildSystemPrompt() {
        return `You are Bob, a friendly and professional assistant for students of the international mobility platform at Aix-Marseille Université (AMU).

Your role:
- Help students with their international mobility (internship abroad, exchange semester)
- Guide them through uploading documents, tracking their folder, finding partner universities
- Answer questions about the platform features

Your personality:
- Warm, encouraging — like a helpful older student
- Use "tu" in French, "you" in English
- Concise answers: 3 to 5 sentences max, unless the student needs step-by-step help
- Use **bold** for important words and bullet lists when listing steps

Platform knowledge:
- Students have a personal dossier where they upload documents (CV, cover letter, learning agreement, insurance)
- They can browse partner universities in the Partenaires tab
- Progress shown as percentage on dashboard: 0% = red, 1–49% = orange, 50–99% = yellow, 100% = green
- Coordinators review folders and can send reminders
- Campagnes define the academic year and mobility type (internship or exchange)
- Students first register on MoveOn; the secretary then imports their dossier from MoveOn into the platform, which creates their account automatically

MOBILITY TYPES & STEPS:
There are two main mobility tracks, each split into sub-types with slightly different steps:

**A) STAGE (internship)** — 3 sub-types: Europe + pays tiers (+2 mois), Hors Europe (+2 mois), et -2 mois (courte durée, non financée).
Phases: Préparation → Départ/Début du stage → Fin du stage et retour → Bilan/Témoignages.

Préparation (commune aux 3 types) :
- Organiser le voyage (billets, passeport, visa), chercher un logement
- S'inscrire sur le Fil d'Ariane, se renseigner sur la culture locale, pratiquer la langue
- Prendre un rendez-vous pré-stage avec le SRI (Service des Relations Internationales)
- Vérifier son éligibilité aux bourses (aucune bourse possible pour les stages -2 mois)
- Compléter le FORMULAIRE DE DÉCLARATION DE STAGE sur le portail avec : certificat de scolarité, attestations d'assurance (accident, responsabilité civile, rapatriement), convention de stage AMU signée (tuteur + chef de département + étudiant), RIB, notification CROUS si boursier
- Spécifique Europe +2mois : CEAM (Carte Européenne d'Assurance Maladie) + contrat pédagogique SMT Erasmus signé + original du contrat de financement Erasmus transmis au SRI
- Spécifique Québec (tous types) : formulaire SE401Q à transmettre par mail au SRI

Début du stage (commun) :
- Compléter le FORMULAIRE D'ARRIVÉE sur le portail (déclenche le 1er versement de la bourse), avec attestation de présence "arrivée" et convention/contrat pédagogique signés par l'organisme d'accueil
- Rester en contact avec son tuteur de stage pendant toute la durée

Fin du stage et retour (commun, variantes selon le type) :
- Compléter le FORMULAIRE DE RETOUR sur le portail avec l'attestation de présence "retour" signée
- Europe +2mois : + page 6 du contrat pédagogique "after the mobility" signée, + sondage "EU Corporate survey" à déposer sur MoveOn
- Hors Europe +2mois et -2mois : + questionnaire "Bilan de fin de séjour" à déposer sur Ametice ou MoveOn
- Tous types : possibilité d'être sollicité par le coordinateur RI du département pour témoigner ou participer à des réunions d'information

**B) MOBILITÉ D'ÉTUDES** — 2 sous-types : courte (-2 mois) et longue (1 semestre ou année complète).

Mobilité d'études courte (-2 mois) :
1. Créer un compte MoveOn (si pas déjà fait)
2. Compléter le formulaire "mobilités d'études courte" avec : certificat de scolarité, attestations d'assurance (responsabilité civile, accident, rapatriement), contrat d'études signé par tous
3. Transmettre l'original du contrat de financement signé au SRI (courrier ou remise en main propre)
4. Préparer la logistique : infos pratiques de l'université d'accueil, logement, transport, visa, langue
5. À l'arrivée : formulaire "à l'arrivée" avec attestation de présence
6. Au retour : formulaire de suivi "au retour" avec attestation de présence, justificatifs de transport, rapport de fin de mobilité
7. Possibilité de témoigner / participer à des réunions d'information

Mobilité d'études longue (1er semestre ou année) :
- Préparer un budget prévisionnel : coût de la vie, éligibilité aux aides AMU, aides du pays/université d'accueil, financements personnels complémentaires
- Consulter les infos pratiques de l'université d'accueil, préparer logement/transport/visa/langue
- Inscription administrative avant le 20 juillet via l'ENT (onglet "réinscription")
- Paiement des droits d'inscription avant fin août
- Formulaire "post-nomination" avant fin mai
- Si Erasmus : contrat de financement original transmis au SRI
- Formulaire "constitution du dossier de mobilité" avant fin août (1er semestre/année) ou fin novembre (2ème semestre), incluant le contrat d'études signé par l'étudiant et le coordinateur RI
- Si départ en Europe : OLA complété et approuvé dans MoveOn
- À l'arrivée (max 10 jours après le départ) : formulaire "à l'arrivée" avec attestation de présence, notification CROUS définitive, certificat de scolarité si tardif, contrat d'études signé par tous si possible (hors Europe)
- Vérifier le versement des aides à la mobilité (1 mois minimum après dépôt de l'attestation d'arrivée)
- Pendant la mobilité : formulaire "pendant la mobilité" si retard/changement de contrat d'études (hors Europe)
- Au retour (max 10 jours après le retour) : formulaire de suivi "au retour" avec attestation de présence, justificatifs de transport (mobilité verte), rapport de fin de mobilité
- Possibilité de témoigner / participer à des réunions d'information

Language rules:
- Always reply in the SAME language as the student's message
- Switch naturally if the student switches language
- Default to French if unclear

If you don't know something specific, say so and suggest contacting the coordinator (SRI).
Never invent platform features or steps not listed above.`;
    }

    // ── Events ────────────────────────────────────────────────────────────────

    #initEvents() {
        this.bubble?.addEventListener('click', () => this.#togglePopup());
        this.closeBtn?.addEventListener('click', () => this.#closePopup());
        this.sendBtn?.addEventListener('click', () => this.#handleSend());

        this.inputField?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.#handleSend();
            }
        });

        // Backward compat for any onclick="toggleHelpPopup()" in HTML
        window.toggleHelpPopup = () => this.#togglePopup();
    }

    // ── UI ────────────────────────────────────────────────────────────────────

    #togglePopup() {
        if (!this.popup) return;
        const isHidden = window.getComputedStyle(this.popup).display === 'none';
        this.popup.style.display = isHidden ? 'flex' : 'none';
        this.#savePopupOpen(isHidden);
        if (isHidden && this.history.length === 0 && this.chatContainer?.innerHTML === '') {
            this.#showWelcome();
        }
        if (isHidden) {
            // Scroll to bottom when opening
            requestAnimationFrame(() => {
                if (this.chatContainer) {
                    this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
                }
            });
        }
    }

    #closePopup() {
        if (this.popup) this.popup.style.display = 'none';
        this.#savePopupOpen(false);
    }

    #showWelcome() {
        const welcomes = {
            fr: "Salut ! Je suis <b>Bob</b> 👋 Je suis là pour t'aider avec ta mobilité internationale. Pose-moi n'importe quelle question sur ton dossier, les partenaires ou la plateforme !",
            en: "Hey! I'm <b>Bob</b> 👋 I'm here to help with your international mobility. Ask me anything about your folder, partner universities, or the platform!"
        };
        this.#addMessage(welcomes[this.currentLang] ?? welcomes.fr, 'bot');
        // Premier message : on scrolle en bas normalement, il n'y a rien à ancrer
        if (this.chatContainer) {
            this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
        }
        this.#renderQuickActions();
    }

    #renderQuickActions() {
        if (!this.quickActions) return;
        this.quickActions.innerHTML = '';

        const actions = {
            fr: ['📁 Mon dossier', '🌍 Trouver un partenaire', '📄 Documents requis', '📊 Mon avancement'],
            en: ['📁 My folder',   '🌍 Find a partner',       '📄 Required documents', '📊 My progress']
        };

        const lang = this.currentLang in actions ? this.currentLang : 'fr';
        actions[lang].forEach(label => {
            const btn = document.createElement('button');
            btn.textContent = label;
            btn.addEventListener('click', () => {
                this.quickActions.innerHTML = '';
                this.#sendMessage(label);
            });
            this.quickActions.appendChild(btn);
        });
    }

    // Ne scrolle plus automatiquement — retourne l'élément créé pour que
    // l'appelant décide où ancrer le scroll (voir #scrollToTop / #sendMessage)
    #addMessage(html, sender) {
        if (!this.chatContainer) return null;
        const div = document.createElement('div');
        div.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
        div.innerHTML = html;
        this.chatContainer.appendChild(div);
        return div;
    }

    // Fait défiler la zone de messages pour placer "el" tout en haut
    // de la zone visible (au lieu de tout défiler jusqu'en bas).
    // Le double requestAnimationFrame garantit que le positionnement
    // s'applique APRÈS que le navigateur ait fini son propre reflow/paint
    // (y compris un éventuel ajustement de scroll anchoring), pour que
    // notre scroll manuel ne soit pas écrasé juste après.
    #scrollToTop(el) {
        if (!el || !this.chatContainer) return;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.chatContainer.scrollTop = el.offsetTop - 10;
            });
        });
    }

    #showTyping() {
        const div = document.createElement('div');
        div.classList.add('message', 'bot-message');
        div.id = 'bob-typing';
        div.innerHTML = '<span class="bob-dot"></span><span class="bob-dot"></span><span class="bob-dot"></span>';
        this.chatContainer?.appendChild(div);
        // Pas de scroll ici : on garde la position ancrée sur le message de l'étudiant
    }

    #hideTyping() {
        document.getElementById('bob-typing')?.remove();
    }

    #setLoading(on) {
        if (this.sendBtn)    this.sendBtn.disabled    = on;
        if (this.inputField) this.inputField.disabled = on;
        if (this.sendBtn)    this.sendBtn.textContent = on ? '…' : '➤';
    }

    // ── Messaging ─────────────────────────────────────────────────────────────

    #handleSend() {
        const text = this.inputField?.value.trim();
        if (!text) return;
        this.inputField.value = '';
        this.#sendMessage(text);
    }

    async #sendMessage(text) {
        // On ajoute le message de l'étudiant et on ancre immédiatement
        // le scroll dessus : c'est ce point de repère qui reste fixe
        // pendant toute la réponse du bot, même si elle est longue.
        const userMsgEl = this.#addMessage(this.#escape(text), 'user');
        this.#scrollToTop(userMsgEl);

        this.history.push({ role: 'user', content: text });
        this.#saveHistory();
        this.#showTyping();
        this.#setLoading(true);

        try {
            const reply = await this.#callApi();
            this.#hideTyping();
            this.#addMessage(this.#md(reply), 'bot');
            this.history.push({ role: 'assistant', content: reply });
            this.#saveHistory();
            // On ré-ancre sur le message de l'étudiant plutôt que de
            // descendre jusqu'à la fin de la réponse du bot
            this.#scrollToTop(userMsgEl);
        } catch (err) {
            this.#hideTyping();
            const msg = {
                fr: "Désolé, je rencontre un problème technique. Réessaie dans quelques instants 🙏",
                en: "Sorry, I'm having a technical issue. Please try again in a moment 🙏"
            };
            this.#addMessage(msg[this.currentLang] ?? msg.fr, 'bot');
            this.#scrollToTop(userMsgEl);
            console.error('Bob error:', err);
        } finally {
            this.#setLoading(false);
        }
    }

    // ── API ───────────────────────────────────────────────────────────────────

    async #callApi() {
        // Calls the PHP proxy — never exposes the API key in the browser
        const res = await fetch('index.php?page=api/bob', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                system:   this.systemPrompt,
                messages: this.history
            })
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        return data.reply;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    #escape(t) {
        return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    #md(text) {
        return text
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
            .replace(/\*(.*?)\*/g,     '<i>$1</i>')
            .replace(/^- (.+)$/gm,     '<li>$1</li>')
            .replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>')
            .replace(/\n/g, '<br>');
    }
    clearHistory() {
        try {
            localStorage.removeItem(this.#storageKey('history'));
            localStorage.removeItem(this.#storageKey('open'));
        } catch (e) {}
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatbot = new BobChatbot();
    window.toggleHelpPopup = () => window.chatbot.popup &&
        window.chatbot._BobChatbot__togglePopup();
});