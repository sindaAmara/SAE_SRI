/**
 * Class representing the Chatbot assistant.
 * Handles the logic for displaying help messages based on user role and language.
 */
class Chatbot {
    constructor() {
        // Predefined knowledge base
        this.knowledgeBase = {
            'admin': {
                'fr': {
                    'intro': "Bonjour Admin ! Je peux vous aider à gérer les dossiers.",
                    'keywords': {
                        'Modifier': "Pour <b>modifier un dossier</b> : Cliquez sur le nom de l'étudiant dans le tableau de bord ou dans la page dossier.",
                        'Ajouter dossier': "Allez dans l'onglet 'Dossiers' et cliquez sur le bouton 'Créer' en haut.",
                        'Partenaire': "Allez dans l'onglet 'Partenaires' pour ajouter une entreprise.",
                        'Relance': "Les dossiers rouges sont incomplets. Contactez l'étudiant via l'icône Email dans la page du tableau de bord.",
                        'Avancement': "Vert = Validé, Orange = En cours, Rouge = Incomplet.",
                        'default': "Aide : modifier, ajouter dossier, partenaire, relance, avancement."
                    }
                },
                'en': {
                    'intro': "Hello Admin! I can help you manage mobilities.",
                    'keywords': {
                        'Modify': "To <b>edit</b>: Click on the student name in the list.",
                        'Add Folder': "Go to 'Folders' tab and click 'Create'.",
                        'Partner': "Go to 'Partners' tab to add a company.",
                        'Relaunch': "Red folders are incomplete. Contact student via Email icon.",
                        'Advancement': "Green = Done, Orange = In Progress, Red = Incomplete.",
                        'default': "Help: edit, add folder, partner, reminder, progress."
                    }
                }
            },
            'student': {
                'fr': {
                    'intro': "Bonjour, je m'appelle Bob ! Je suis là pour t'aider dans ta mobilité.",
                    'keywords': {
                        'Déposer': "Va dans 'Mon Folder' pour téléverser tes fichiers (CV, Lettre...).",
                        'Avancé': "Ta progression est indiquée sur ton tableau de bord .",
                        'Partenaire': "Consulte l'onglet 'Partenaires' pour voir les entreprises disponibles.",
                        'Convention': "La convention doit être signée et uploadée dans 'Pièces Justificatives'.",
                        'default': "Aide : déposer, avancement, partenaire, convention."
                    }
                },
                'en': {
                    'intro': "Hi, my name is Bob ! I'm here to help with your mobility.",
                    'keywords': {
                        'Deposit': "Go to 'My Folder' to upload documents.",
                        'Advance': "Your progress is shown on your dashboard.",
                        'Partner': "Check 'Partners' tab for companies.",
                        'Convention': "The agreement must be signed and uploaded.",
                        'default': "Help: submit, progress, partner, agreement."
                    }
                }
            }
        };

        // Configuration
        const configEl = document.getElementById('app-config');
        this.currentLang = configEl ? configEl.dataset.lang : 'fr';
        this.userRole = configEl ? configEl.dataset.role : 'student';

        // DOM Elements
        this.bubble = document.getElementById('help-bubble');
        this.popup = document.getElementById('help-popup');
        this.closeBtn = document.querySelector('#help-popup button');
        this.chatContainer = document.getElementById('chat-messages');

        this.initEvents();
    }

    /**
     * Initializes event listeners for the chatbot bubble and popup.
     */
    initEvents() {
        if (this.bubble && this.popup) {
            this.bubble.addEventListener('click', () => this.togglePopup());
        }

        if (this.closeBtn && this.popup) {
            this.closeBtn.addEventListener('click', () => {
                this.popup.style.display = 'none';
            });
        }
    }

    /**
     * Toggles the visibility of the chatbot popup.
     */
    togglePopup() {
        if (!this.popup) return;
        
        const isHidden = (window.getComputedStyle(this.popup).display === 'none');
        this.popup.style.display = isHidden ? 'flex' : 'none';
        
        // Initialize chat content if empty
        if (isHidden && this.chatContainer && this.chatContainer.innerHTML === '') {
            const welcomeMsg = this.knowledgeBase[this.userRole][this.currentLang]['intro'];
            this.addMessage(welcomeMsg, 'bot');
            this.generateQuickActions();
        }
    }

    /**
     * Adds a message to the chat container.
     * @param {string} text - The message content.
     * @param {string} sender - The sender ('user' or 'bot').
     */
    addMessage(text, sender) {
        if (!this.chatContainer) return;
        
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');
        msgDiv.innerHTML = text;
        this.chatContainer.appendChild(msgDiv);
        
        // Auto-scroll to bottom
        this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
    }

    /**
     * Generates quick action buttons based on the user's role and language.
     */
    generateQuickActions() {
        const container = document.getElementById('quick-actions');
        if (!container) return;

        const roleDict = this.knowledgeBase[this.userRole][this.currentLang]['keywords'];

        for (const key in roleDict) {
            if (key === 'default') continue;
            const btn = document.createElement('button');
            btn.innerText = key.charAt(0).toUpperCase() + key.slice(1);
            btn.onclick = () => {
                this.addMessage(btn.innerText, 'user');
                // Simulate bot thinking delay
                setTimeout(() => this.addMessage(roleDict[key], 'bot'), 400);
            };
            container.appendChild(btn);
        }
    }
}

// Instantiate and expose backward compatibility
document.addEventListener('DOMContentLoaded', () => {
    window.chatbot = new Chatbot();
    // Fallback for any lingering <div onclick="toggleHelpPopup()"> elements
    window.toggleHelpPopup = () => window.chatbot.togglePopup();
});