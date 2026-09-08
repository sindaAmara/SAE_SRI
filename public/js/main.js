/**
 * Main application class managing global UI features:
 * - Responsive menu
 * - Language switching
 * - Accessibility theme toggle (tritanopia)
 * - Auto-dismissable toast messages
 */
class MainApp {
    constructor() {
        this.initConfig();
        this.initMenu();
        this.initLanguageDropdown();
        this.initThemeToggle();
        this.initAutoDismissMessages();
    }

    /**
     * Parses and stores global app configuration from the DOM.
     * Sets default language ('fr') and role ('student') if missing.
     */
    initConfig() {
        const configEl = document.getElementById('app-config');
        window.AppConfig = {
            lang: configEl?.dataset.lang || 'fr',
            role: configEl?.dataset.role || 'student'
        };
    }

    /**
     * Initializes the responsive hamburger menu for mobile.
     * Toggles the navigation menu visibility.
     */
    initMenu() {
        const menuToggle = document.createElement('button');
        menuToggle.classList.add('menu-toggle');
        menuToggle.innerHTML = '☰';

        const rightBtn = document.querySelector('.right-buttons');
        if (rightBtn) rightBtn.appendChild(menuToggle);

        const navMenu = document.querySelector('nav.menu');
        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', () => navMenu.classList.toggle('active'));
        }
    }

    /**
     * Initializes the language selector dropdown.
     * Clicking outside closes the dropdown.
     */
    initLanguageDropdown() {
        const langBtn = document.querySelector('.dropbtn');
        if (langBtn) {
            langBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                langBtn.parentElement?.classList.toggle('show');
            });
        }

        document.addEventListener('click', () => {
            const dropdown = document.querySelector('.lang-dropdown');
            dropdown?.classList.remove('show');
        });
    }

    /**
     * Initializes the tritanopia accessibility theme toggle.
     * Updates URL parameter to persist state.
     */
    initThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        if (!themeToggle) return;

        // Apply initial state
        if (document.body.classList.contains('tritanopie')) themeToggle.classList.add('active');

        themeToggle.addEventListener('click', (e) => {
            document.body.classList.toggle('tritanopie');
            e.currentTarget.classList.toggle('active');

            const isTritanopia = document.body.classList.contains('tritanopie') ? '1' : '0';
            const url = new URL(window.location.href);
            url.searchParams.set('tritanopia', isTritanopia);
            window.location.href = url.toString();
        });
    }

    /**
     * Automatically dismisses toast messages (success/error) after 5 seconds.
     * Chatbot messages are excluded from auto-dismissal.
     */
    initAutoDismissMessages() {
        const messages = document.querySelectorAll('.message, .success-message, .error-message');
        messages.forEach(msg => {
            if (!msg.classList.contains('user-message') && !msg.classList.contains('bot-message')) {
                setTimeout(() => {
                    msg.style.transition = 'opacity 0.5s ease';
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 500);
                }, 5000);
            }
        });
    }

    /**
     * Changes the application language and reloads the page.
     * @param {string} lang - Language code (e.g., 'fr', 'en').
     */
    changeLang(lang) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
}

// Initialize MainApp on DOM ready and provide fallback for inline onclick
document.addEventListener('DOMContentLoaded', () => {
    window.mainApp = new MainApp();
    window.changeLang = (lang) => window.mainApp.changeLang(lang);
});