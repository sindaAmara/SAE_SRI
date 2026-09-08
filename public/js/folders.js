/* ==========================================================================
   FolderManager.js
   ========================================================================== */


/* --------------------------------------------------------------------------
   FormManager — formulaire principal & mode modification
   -------------------------------------------------------------------------- */
class FormManager {
    constructor() {
        this._initMobiliteSelect();
        this._initBtnModifier();
    }

    _initMobiliteSelect() {
        const mobiliteSelect = document.getElementById('mobilite_type');
        if (!mobiliteSelect) return;
        this.changerTypeMobilite(mobiliteSelect.value);
        mobiliteSelect.addEventListener('change', (e) => {
            this.changerTypeMobilite(e.target.value);
            // Synchroniser le hidden miroir quand le select change
            const hidden = document.getElementById('hidden_mobilite_type');
            if (hidden) hidden.value = e.target.value;
        });
    }

    _initBtnModifier() {
        const btnModifier = document.getElementById('btn-modifier');
        if (btnModifier) btnModifier.addEventListener('click', () => this.activerModification());
    }

    changerTypeMobilite(type) {
        const conventionBlock = document.getElementById('justificatif_convention');
        const lettreBlock     = document.getElementById('lettre_motivation');

        if (conventionBlock) conventionBlock.style.display = 'none';
        if (lettreBlock)     lettreBlock.style.display     = 'none';

        if (type === 'stage'  && conventionBlock) conventionBlock.style.display = 'block';
        if (type === 'etudes' && lettreBlock)     lettreBlock.style.display     = 'block';
    }

    activerModification() {
        const formPrincipal = document.querySelector('.creation-form');
        if (!formPrincipal) return;

        formPrincipal.querySelectorAll('input:not([type="file"]):not([type="hidden"]), select').forEach(field => {
            if (field.id !== 'numetu' && field.id !== 'numetu_display') {
                field.setAttribute('data-original-value', field.value);
            }
        });

        formPrincipal.querySelectorAll('input').forEach(field => {
            if (field.id !== 'numetu' && field.id !== 'numetu_display') {
                field.removeAttribute('readonly');
                field.disabled = false;
                field.style.backgroundColor = 'white';
                field.style.color = 'black';
                field.classList.remove('input-disabled');
            }
        });

        formPrincipal.querySelectorAll('select').forEach(field => {
            if (field.id !== 'numetu' && field.id !== 'numetu_display') {
                field.disabled = false;
                field.style.backgroundColor = 'white';
                field.style.color = 'black';
                field.classList.remove('input-disabled');
                const mirror = formPrincipal.querySelector(`input[type="hidden"][name="${field.name}"].select-mirror`);
                if (mirror) mirror.disabled = true;
            }
        });

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.disabled = false;
            input.classList.remove('input-disabled');
        });

        document.querySelectorAll('.doc-actions textarea').forEach(ta => { ta.disabled = false; });
        document.querySelectorAll('.btn-status').forEach(btn => { btn.disabled = false; });

        const btnMod    = document.getElementById('btn-modifier');
        const btnSave   = document.getElementById('btn-enregistrer');
        const btnCancel = document.getElementById('btn-annuler');

        if (btnMod)    btnMod.style.display = 'none';
        if (btnSave)   { btnSave.style.display = 'inline-block';   btnSave.classList.remove('btn-hidden'); }
        if (btnCancel) { btnCancel.style.display = 'inline-block'; btnCancel.classList.remove('btn-hidden'); }
    }
}


/* --------------------------------------------------------------------------
   FilterManager — recherche & filtres → URL
   -------------------------------------------------------------------------- */
class FilterManager {
    constructor() {
        this._initSearchEvents();
        this._initCheckboxFilters();
        this._initSelectFilters();
    }

    _initSearchEvents() {
        const searchInput = document.getElementById('search');
        const searchBtn   = document.querySelector('#btn-search-loupe');

        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.appliquerFiltres(true), 3000);
            });
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.appliquerFiltres(true);
            });
        }

        if (searchBtn) searchBtn.addEventListener('click', () => this.appliquerFiltres(true));
    }

    _initCheckboxFilters() {
        document.querySelectorAll('input[name="entrant_sortant"], input[name="zone"]').forEach(cb => {
            cb.addEventListener('click', (e) => {
                const groupName = e.target.name;
                document.querySelectorAll(`input[name="${groupName}"]`).forEach(other => {
                    if (other !== e.target) other.checked = false;
                });
                this.appliquerFiltres(true);
            });
        });
    }

    _initSelectFilters() {
        document.querySelectorAll('#filter-complet, #date-debut, #date-fin, #filter-composante, #filter-accord').forEach(sel => {
            if (sel) sel.addEventListener('change', () => this.appliquerFiltres(true));
        });
    }

    appliquerFiltres(resetPage = false) {
        const url = new URL(window.location.href);

        const searchInput = document.getElementById('search');
        if (searchInput && searchInput.value.trim() !== '') url.searchParams.set('search', searchInput.value.trim());
        else url.searchParams.delete('search');

        const typeChecked = document.querySelector('input[name="entrant_sortant"]:checked');
        if (typeChecked) url.searchParams.set('type', typeChecked.value);
        else             url.searchParams.delete('type');

        const zoneChecked = document.querySelector('input[name="zone"]:checked');
        if (zoneChecked) url.searchParams.set('zone', zoneChecked.value);
        else             url.searchParams.delete('zone');

        const completVal = document.getElementById('filter-complet');
        if (completVal && completVal.value !== 'all') url.searchParams.set('complet', completVal.value);
        else                                           url.searchParams.delete('complet');

        const composanteVal = document.getElementById('filter-composante');
        if (composanteVal && composanteVal.value !== 'all') url.searchParams.set('composante', composanteVal.value);
        else                                                 url.searchParams.delete('composante');

        const accordVal = document.getElementById('filter-accord');
        if (accordVal && accordVal.value !== 'all') url.searchParams.set('accord', accordVal.value);
        else                                         url.searchParams.delete('accord');

        if (resetPage) url.searchParams.delete('p');
        window.location.href = url.toString();
    }
}


/* --------------------------------------------------------------------------
   TableManager — clics sur les lignes → fiche étudiant
   -------------------------------------------------------------------------- */
class TableManager {
    constructor() {
        document.querySelectorAll('.table-etudiants tbody tr').forEach(row => {
            row.addEventListener('click', (e) => {
                const numetu = e.currentTarget.dataset.numetu;
                if (numetu) this.ouvrirFicheEtudiant(numetu);
            });
        });
    }

    ouvrirFicheEtudiant(numetu) {
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'view');
        url.searchParams.set('numetu', numetu);
        window.location.href = url.toString();
    }
}


/* --------------------------------------------------------------------------
   AccordionManager — toggle + pagination interne par section
   -------------------------------------------------------------------------- */
class AccordionManager {
    constructor(itemsPerPage = 10) {
        this.itemsPerPage = itemsPerPage;
        this._initToggle();
        this._initPagination();
    }

    _initToggle() {
        document.querySelectorAll('.barre-titre').forEach(barre => {
            barre.addEventListener('click', () => {
                const contenu = document.getElementById(barre.getAttribute('data-target'));
                const fleche  = barre.querySelector('.fleche');
                if (contenu) contenu.classList.toggle('afficher');
                if (fleche)  fleche.classList.toggle('ouverte');
            });
        });
    }

    _initPagination() {
        document.querySelectorAll('.section-composante').forEach((section) => {
            const tbody               = section.querySelector('.table-etudiants tbody');
            const paginationContainer = section.querySelector('.accordion-pagination');
            if (!tbody || !paginationContainer) return;

            const rows = Array.from(tbody.querySelectorAll('tr'));
            if (rows.length <= this.itemsPerPage) { paginationContainer.style.display = 'none'; return; }

            const totalPages = Math.ceil(rows.length / this.itemsPerPage);

            const showPage = (page) => {
                rows.forEach((row, index) => {
                    row.style.display = (index >= (page - 1) * this.itemsPerPage && index < page * this.itemsPerPage) ? '' : 'none';
                });
                renderButtons(page);
            };

            const renderButtons = (currentPage) => {
                paginationContainer.innerHTML = '';

                const btnPrev = document.createElement('button');
                btnPrev.textContent = '‹';
                btnPrev.disabled    = currentPage === 1;
                btnPrev.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); showPage(currentPage - 1); });
                paginationContainer.appendChild(btnPrev);

                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.textContent = i;
                    if (i === currentPage) btn.classList.add('active');
                    btn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); showPage(i); });
                    paginationContainer.appendChild(btn);
                }

                const btnNext = document.createElement('button');
                btnNext.textContent = '›';
                btnNext.disabled    = currentPage === totalPages;
                btnNext.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); showPage(currentPage + 1); });
                paginationContainer.appendChild(btnNext);
            };

            showPage(1);
        });
    }
}


/* --------------------------------------------------------------------------
   DocumentManager — AJAX confirm/upload, statut global, boutons statut, upload unlock
   -------------------------------------------------------------------------- */
class DocumentManager {
    constructor() {
        this._initStatutButtons();
        this._initFileUploadEvents();
    }

    _initStatutButtons() {
        document.querySelectorAll('.statut-document-buttons .btn-status').forEach(btn => {
            btn.addEventListener('click', function () {
                const doc = this.dataset.doc;
                document.querySelectorAll(`.statut-document-buttons[data-doc="${doc}"] .btn-status`).forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    _initFileUploadEvents() {
        document.querySelectorAll('.doc-review-item input[type="file"]').forEach(fileInput => {
            fileInput.addEventListener('change', function () {
                if (!this.files || this.files.length === 0) return;
                const item = this.closest('.doc-review-item');
                if (!item) return;
                item.querySelectorAll('input[type="radio"], textarea, .btn-confirm-doc').forEach(el => {
                    el.disabled = false;
                    el.classList.remove('input-disabled');
                });
                const docActions = item.querySelector('.doc-actions');
                if (docActions) docActions.classList.remove('disabled-area');
            });
        });
    }

    async confirmDocument(numEtu, docType) {
        const container = document.querySelector(`.doc-review-item[data-doctype="${docType}"]`);
        if (!container) return;

        const checkedRadio  = container.querySelector(`input[name="status_${docType}"]:checked`);
        const status        = checkedRadio ? checkedRadio.value : 'pending';
        const comment       = container.querySelector(`textarea[name="comment_${docType}"]`)?.value ?? '';
        const indicator     = document.getElementById(`indicator_${docType}`);
        const btn           = container.querySelector('.btn-confirm-doc');
        const fileInputName = docType === 'langues' ? 'langues_file' : docType;
        const fileInput     = container.querySelector(`input[type="file"][name="${fileInputName}"]`);
        const hasNewFile    = fileInput && fileInput.files && fileInput.files.length > 0;

        if (btn)       btn.disabled = true;
        if (indicator) { indicator.textContent = "Sauvegarde en cours..."; indicator.style.color = "orange"; }

        if (hasNewFile) {
            const formData = new FormData();
            formData.append('numetu',   numEtu);
            formData.append('doc_type', docType);
            formData.append('status',   status);
            formData.append('comment',  comment);
            formData.append('file',     fileInput.files[0]);

            try {
                const result = await (await fetch('index.php?page=update_document_status', { method: 'POST', body: formData })).json();
                if (indicator) {
                    indicator.textContent = result.success ? "Enregistré ✓" : (result.message || "Erreur serveur");
                    indicator.style.color = result.success ? "green" : "red";
                }
                if (result.success) setTimeout(() => window.location.reload(), 800);
            } catch {
                if (indicator) { indicator.textContent = "Erreur réseau"; indicator.style.color = "red"; }
            }

            setTimeout(() => { if (indicator) indicator.textContent = ""; if (btn) btn.disabled = false; }, 3000);
            return;
        }

        const formData = new FormData();
        formData.append('numetu',   numEtu);
        formData.append('doc_type', docType);
        formData.append('status',   status);
        formData.append('comment',  comment);

        try {
            const result = await (await fetch('index.php?page=update_document_status', { method: 'POST', body: formData })).json();
            if (indicator) {
                indicator.textContent = result.success ? "Enregistré ✓" : "Erreur serveur";
                indicator.style.color = result.success ? "green" : "red";
            }
        } catch {
            if (indicator) { indicator.textContent = "Erreur réseau"; indicator.style.color = "red"; }
        }

        setTimeout(() => { if (indicator) indicator.textContent = ""; if (btn) btn.disabled = false; }, 3000);
    }

    async updateGlobalStatus(numEtu) {
        const statusSelect = document.getElementById('global_status_select');
        const indicator    = document.getElementById('global_status_indicator');
        if (!statusSelect) return;

        if (indicator) { indicator.textContent = "Sauvegarde en cours..."; indicator.style.color = "orange"; }

        const formData = new FormData();
        formData.append('numetu', numEtu);
        formData.append('status', statusSelect.value);

        try {
            const result = await (await fetch('index.php?page=update_global_status', { method: 'POST', body: formData })).json();
            if (indicator) {
                indicator.textContent = result.success ? "Statut mis à jour ✓" : "Erreur";
                indicator.style.color = result.success ? "green" : "red";
            }
        } catch {
            if (indicator) { indicator.textContent = "Erreur réseau"; indicator.style.color = "red"; }
        }

        setTimeout(() => { if (indicator) indicator.textContent = ""; }, 3000);
    }
}


/* --------------------------------------------------------------------------
   DateLimiteManager — toggle formulaire date limite
   -------------------------------------------------------------------------- */
class DateLimiteManager {
    constructor() {
        const btnEditDate    = document.getElementById('btn-edit-date-limite');
        const formDateLimite = document.getElementById('form-date-limite');
        const btnCancelDate  = document.getElementById('btn-cancel-date');

        if (btnEditDate && formDateLimite) {
            btnEditDate.addEventListener('click', () => {
                formDateLimite.style.display = formDateLimite.style.display === 'none' ? 'block' : 'none';
            });
        }
        if (btnCancelDate && formDateLimite) {
            btnCancelDate.addEventListener('click', () => { formDateLimite.style.display = 'none'; });
        }
    }
}


/* --------------------------------------------------------------------------
   ValidationModalManager — modale de confirmation avant soumission
   -------------------------------------------------------------------------- */
class ValidationModalManager {
    constructor() {
        this._init();
    }

    _init() {
        const btnEnregistrer = document.getElementById('btn-enregistrer');
        const modal          = document.getElementById('modal-validation');
        const btnModalCancel = document.getElementById('btn-modal-cancel');
        const formPrincipal  = document.querySelector('.creation-form');
        const formValidation = document.getElementById('form-validation');

        if (!btnEnregistrer || !modal || !formPrincipal) return;

        const lang         = document.getElementById('app-config')?.dataset.lang ?? 'fr';
        const translations = {
            photo:             lang === 'fr' ? "Photo d'identité"          : 'ID Photo',
            cv:                lang === 'fr' ? 'CV'                         : 'Resume',
            convention:        lang === 'fr' ? 'Convention de stage'        : 'Internship Agreement',
            lettre_motivation: lang === 'fr' ? 'Lettre de motivation'       : 'Motivation Letter',
            langues:           lang === 'fr' ? 'Attestation de langues'     : 'Language Certificate',
            conforme:          lang === 'fr' ? 'Accepté'                    : 'Accepted',
            non_conforme:      lang === 'fr' ? 'Refusé'                     : 'Refused',
            manquant:          lang === 'fr' ? 'Manquant'                   : 'Missing',
            present:           lang === 'fr' ? 'Déposé'                     : 'Uploaded',
            aucun_manquant:    lang === 'fr' ? '✅ Aucun document manquant'  : '✅ No missing documents',
        };

        btnEnregistrer.addEventListener('click', (e) => {
            e.preventDefault();
            this._afficherModifications(this._detecterModifications(formPrincipal));
            // Analyse dynamique au moment du clic : on inspecte le DOM,
            // plus fiable que window.analyseDocumentsData (souvent absent).
            const analyseDocuments = this._analyserDocumentsDepuisDOM();
            this._afficherDocuments(analyseDocuments, translations);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        if (btnModalCancel) btnModalCancel.addEventListener('click', () => this._fermerModale(modal));
        modal.addEventListener('click', (e) => { if (e.target === modal) this._fermerModale(modal); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) this._fermerModale(modal);
        });

        if (formValidation) {
            formValidation.addEventListener('submit', (e) => {
                e.preventDefault();
                this._syncFormToModal(formPrincipal, formValidation);
                formValidation.submit();
            });
        }

        const msgDiv = document.querySelector('.message');
        if (msgDiv && msgDiv.textContent.trim() !== '') {
            msgDiv.style.display = 'block';
            setTimeout(() => msgDiv.remove(), 3500);
        }
    }

    /**
     * Analyse l'état réel des documents directement depuis le DOM.
     * Un document est "présent" si son .doc-review-item ne contient PAS .no-document,
     * ou si un fichier a été sélectionné dans l'input file associé.
     * Un document est "manquant" dans tous les autres cas.
     */
    _analyserDocumentsDepuisDOM() {
        const manquants = [];
        const presents  = [];
        const statuts   = {};

        document.querySelectorAll('.doc-review-item').forEach(item => {
            const doc = item.dataset.doctype;
            if (!doc) return;

            const hasNoDocSpan = !!item.querySelector('.no-document');
            const fileInput    = item.querySelector('input[type="file"]');
            const hasNewFile   = fileInput && fileInput.files && fileInput.files.length > 0;
            const downloadLink = item.querySelector('.btn-download');

            const estPresent = !hasNoDocSpan || hasNewFile || !!downloadLink;

            if (estPresent) {
                presents.push(doc);
                const checkedRadio = item.querySelector(`input[name="status_${doc}"]:checked`);
                statuts[doc] = checkedRadio ? checkedRadio.value : 'pending';
            } else {
                manquants.push(doc);
            }
        });

        return { manquants, presents, statuts };
    }

    _fermerModale(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    _syncFormToModal(formPrincipal, formModal) {
        formModal.querySelectorAll('.synced-field').forEach(el => el.remove());

        formPrincipal.querySelectorAll('input:not([type="file"]), select, textarea').forEach(field => {
            if (!field.name) return;
            if (formModal.querySelector(`[name="${field.name}"]`)) return;

            let value = '';
            if (field.tagName === 'SELECT') {
                if (field.disabled) {
                    const mirror = formPrincipal.querySelector(`input[type="hidden"][name="${field.name}"]`);
                    value = mirror ? mirror.value : '';
                } else {
                    value = field.options[field.selectedIndex]?.value ?? '';
                }
            } else {
                value = field.value;
            }

            // DEBUG — à supprimer après
            if (field.name === 'mobilite_type') {
                console.log('mobilite_type synced value:', value);
            }

            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = field.name;
            hidden.value = value;
            hidden.classList.add('synced-field');
            formModal.appendChild(hidden);
        });
    }

    _detecterModifications(formPrincipal) {
        const modifications = [];

        formPrincipal.querySelectorAll('input:not([type="file"]):not([type="hidden"]), select').forEach(input => {
            if (input.disabled) return;
            if (!input.name || input.name === 'numetu' || input.name === 'mobilite_type') return;

            const valeurOriginale = input.getAttribute('data-original-value');
            if (valeurOriginale === null) return;

            const valeurActuelle = input.value;
            if (valeurActuelle === valeurOriginale) return;

            let label = input.name;
            if (input.id) {
                const labelEl = formPrincipal.querySelector(`label[for="${input.id}"]`);
                if (labelEl) label = labelEl.textContent.trim().replace('*', '').trim();
            }

            modifications.push({ champ: label, ancienne: valeurOriginale || '(vide)', nouvelle: valeurActuelle || '(vide)' });
        });

        return modifications;
    }

    _afficherModifications(modifications) {
        const section = document.getElementById('section-modifications');
        const liste   = document.getElementById('liste-modifications');
        if (!section || !liste) return;

        if (modifications.length === 0) { section.style.display = 'none'; return; }

        section.style.display = 'block';
        liste.innerHTML = modifications.map(m =>
            `<li>
                <strong>${m.champ}</strong> :
                <span style="color:#dc3545;text-decoration:line-through;">${m.ancienne}</span>
                → <span style="color:#28a745;font-weight:600;">${m.nouvelle}</span>
             </li>`
        ).join('');
    }

    _afficherDocuments(analyseDocuments, translations) {
        const manquants = analyseDocuments.manquants || [];
        const presents  = analyseDocuments.presents  || [];
        const statuts   = analyseDocuments.statuts   || {};

        const statutsVue = {};
        document.querySelectorAll('.doc-review-item').forEach(item => {
            const doc          = item.dataset.doctype;
            const checkedRadio = doc ? item.querySelector(`input[name="status_${doc}"]:checked`) : null;
            if (doc && checkedRadio) statutsVue[doc] = checkedRadio.value;
        });

        const formValidation = document.getElementById('form-validation');
        document.querySelectorAll('[id^="statut_modal_"]').forEach(el => el.remove());

        presents.forEach(doc => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'statut_' + doc;
            input.id    = 'statut_modal_' + doc;
            input.value = statutsVue[doc] || statuts[doc] || 'pending';
            if (formValidation) formValidation.appendChild(input);
        });

        const listeManquants = document.getElementById('liste-manquants');
        if (listeManquants) {
            listeManquants.innerHTML = manquants.length === 0
                ? `<p style="color:#28a745;font-weight:600;">${translations.aucun_manquant}</p>`
                : manquants.map(doc => `
                    <div class="document-validation-item manquant">
                        <span class="document-name">${translations[doc] || doc}</span>
                        <span class="document-status-badge badge-manquant">${translations.manquant}</span>
                    </div>`
                ).join('');
        }

        const listePresents = document.getElementById('liste-presents');
        if (listePresents) {
            listePresents.innerHTML = presents.map(doc => {
                const s     = statutsVue[doc] || statuts[doc] || '';
                const badge = s === 'accepted'
                    ? `<span class="document-status-badge" style="background:#28a745;color:white;">✅ ${translations.conforme}</span>`
                    : s === 'refused'
                        ? `<span class="document-status-badge" style="background:#dc3545;color:white;">❌ ${translations.non_conforme}</span>`
                        : `<span class="document-status-badge badge-present">${translations.present}</span>`;
                return `
                    <div class="document-validation-item present">
                        <span class="document-name">${translations[doc] || doc}</span>
                        ${badge}
                    </div>`;
            }).join('');
        }
    }
}


/* --------------------------------------------------------------------------
   FolderManager — orchestre toutes les classes
   -------------------------------------------------------------------------- */
class FolderManager {
    constructor() {
        this._form       = new FormManager();
        this._filter     = new FilterManager();
        this._table      = new TableManager();
        this._accordion  = new AccordionManager();
        this._document   = new DocumentManager();
        this._modal      = new ValidationModalManager();
        this._dateLimite = new DateLimiteManager();
    }

    /** Proxy — appelé depuis le HTML : folderManager.confirmDocument(numEtu, docType) */
    confirmDocument(numEtu, docType) {
        return this._document.confirmDocument(numEtu, docType);
    }

    /** Proxy — appelé depuis le HTML : folderManager.updateGlobalStatus(numEtu) */
    updateGlobalStatus(numEtu) {
        return this._document.updateGlobalStatus(numEtu);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    window.folderManager = new FolderManager();
});