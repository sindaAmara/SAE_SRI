/**
 * Class handling Super Admin panel actions:
 * - Confirming deletions
 * - Toggling role-specific form fields
 * - Generating secure passwords
 * - Adding departments and sites
 */
class SuperAdminManager {

    /**
     * Displays a confirmation dialog for deleting a user account.
     * @param {string} login - User login to confirm deletion.
     * @returns {boolean} True if confirmed, false otherwise.
     */
    confirmDelete(login) {
        const configEl = document.getElementById('app-config');
        const label = (configEl && configEl.dataset.deleteLabel) 
            ? configEl.dataset.deleteLabel 
            : 'Supprimer le compte';
            
        return confirm(`${label} ${login} ?`);
    }

    
    /**
     * Shows/hides form fields based on the selected role.
     * @param {string} role - The role selected ('admin' or other).
     */
    toggleRoleFields(role) {
        const deptField      = document.getElementById('deptField');
        const siteField      = document.getElementById('siteField');
        const coordTypeField = document.getElementById('coordTypeField');
        const deptSelect     = document.getElementById('departement');
        const siteSelect     = document.getElementById('site');
        const roleHidden     = document.getElementById('roleHiddenAdmin');

        if (role === 'admin') {
            siteField.style.display      = '';
            deptField.style.display      = 'none';
            coordTypeField.style.display = 'none';
            siteSelect.required          = true;
            deptSelect.required          = false;
            roleHidden.value             = 'admin';
            roleHidden.disabled          = false;
            document.querySelectorAll('input[name="role"]').forEach(r => {
                r.required = false;
                r.checked  = false;
            });
        } else {
            siteField.style.display      = 'none';
            deptField.style.display      = '';
            coordTypeField.style.display = '';
            siteSelect.required          = false;
            deptSelect.required          = true;
            roleHidden.value             = '';
            roleHidden.disabled          = true;
            document.querySelectorAll('input[name="role"]').forEach(r => r.required = true);
        }
    }

    /**
     * Generates a random secure password and sets it in the password field.
     * Ensures at least one uppercase letter and one special character, min 12 chars.
     */
    generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
        const regex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{12,}$/;
        let pwd = '';

        do {
            pwd = '';
            for (let i = 0; i < 12; i++) {
                pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            }
        } while (!regex.test(pwd));

        const el = document.getElementById('password');
        if (el) el.value = pwd;
    }

    /**
     * Private helper to show a colored message in the UI.
     * @param {HTMLElement|null} el - Element to display the message.
     * @param {string} color - CSS color string.
     * @param {string} text - Message text.
     * @private
     */
    _showMsg(el, color, text) {
        if (!el) return;
        el.style.color   = color;
        el.textContent   = text;
        el.style.display = '';
    }

    /**
     * Adds a new department if it doesn't already exist.
     * Validates input and submits the hidden form.
     */
    addDepartment() {
        const input  = document.getElementById('newDeptInput');
        const msg    = document.getElementById('deptMsg');
        const select = document.getElementById('departement');
        if (!input) return;

        const code = input.value.trim().toUpperCase();
        if (!code) {
            this._showMsg(msg, 'red', 'Please enter a code.');
            return;
        }

        for (let opt of select.options) {
            if (opt.value === code) {
                this._showMsg(msg, 'orange', 'Already exists.');
                return;
            }
        }

        const hidden = document.getElementById('hiddenDeptValue');
        const form   = document.getElementById('addDeptForm');
        if (hidden && form) {
            hidden.value = code;
            form.submit();
        }
    }

    /**
     * Adds a new site if it doesn't already exist.
     * Validates input and submits the hidden form.
     */
    addSite() {
        const input  = document.getElementById('newSiteInput');
        const msg    = document.getElementById('siteMsg');
        const select = document.getElementById('site');
        if (!input) return;

        const name = input.value.trim();
        if (!name) {
            this._showMsg(msg, 'red', 'Please enter a name.');
            return;
        }

        for (let opt of select.options) {
            if (opt.value === name) {
                this._showMsg(msg, 'orange', 'Already exists.');
                return;
            }
        }

        const hidden = document.getElementById('hiddenSiteValue');
        const form   = document.getElementById('addSiteForm');
        if (hidden && form) {
            hidden.value = name;
            form.submit();
        }
    }
}

// Instantiate globally so HTML can call methods directly
window.SuperAdmin = new SuperAdminManager();