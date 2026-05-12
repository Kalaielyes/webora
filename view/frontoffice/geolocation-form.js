/**
 * Géolocalisation et adaptation du formulaire de crédit
 * Intégration complète avec ClientGeolocation et GeolocHelper du backend
 */

class CreditFormGeolocation {
    constructor() {
        this.location = null;
        this.rules = null;
        this.init();
    }

    async init() {
        // Récupérer les infos de géolocalisation depuis le serveur
        await this.fetchGeolocation();
        this.updateFormFields();
        this.setupMontantListener();
        this.setupSimulationListener();
    }

    async fetchGeolocation() {
        try {
            const controllerPath = window.CONTROLLER_PATH || '/controller/AdminCreditController.php';
            console.log('[Geoloc] Fetching from:', controllerPath);

            const formData = new FormData();
            formData.append('action', 'get_client_location');
            
            const response = await fetch(controllerPath, {
                method: 'POST',
                body: formData
            });

            console.log('[Geoloc] Response status:', response.status);
            
            if (!response.ok) {
                console.warn('[Geoloc] Response not ok:', response.status, response.statusText);
                return;
            }

            const responseText = await response.text();
            console.log('[Geoloc] Raw response:', responseText);

            const data = JSON.parse(responseText);
            this.location = data.location;
            this.rules = data.rules;

            console.log('[Geoloc] Location detected:', this.location);
            console.log('[Geoloc] Rules:', this.rules);

            // Afficher les infos de géolocalisation
            this.displayGeoInfo();
            
            // Stocker en window pour debug
            window.geoLocation = this.location;
            window.geoRules = this.rules;
        } catch (error) {
            console.error('[Geoloc] Erreur:', error);
            console.error('[Geoloc] Stack:', error.stack);
        }
    }

    displayGeoInfo() {
        if (!this.location) return;

        const infoDiv = document.getElementById('geo-info') || this.createGeoInfoDiv();

        infoDiv.innerHTML = `
            <div class="geo-badge" style="background:linear-gradient(135deg, rgba(59,130,246,0.1), rgba(139,92,246,0.1)); border:1px solid rgba(59,130,246,0.3); border-radius:8px; padding:12px; margin-bottom:16px; display:flex; align-items:center; gap:12px;">
                <span class="geo-flag" style="font-size:32px;">${this.getCountryFlag(this.location.country_code)}</span>
                <div class="geo-details" style="flex:1;">
                    <strong style="font-size:14px; display:block; margin-bottom:4px;">${this.location.country_name}</strong>
                    <small style="color:var(--muted); font-size:12px; display:block; margin-bottom:6px;">${this.location.city ? this.location.city + ', ' : ''}${this.location.region}</small>
                    <div class="geo-currency" style="color:var(--emerald); font-weight:600; font-size:12px;">
                        💱 ${this.location.currency} (${this.location.currency_symbol})
                    </div>
                </div>
            </div>
        `;
    }

    createGeoInfoDiv() {
        const div = document.createElement('div');
        div.id = 'geo-info';

        // Insérer avant le premier formulaire de demande de crédit
        const formEditDem = document.getElementById('form-edit-dem');
        if (formEditDem && formEditDem.parentNode) {
            formEditDem.parentNode.insertBefore(div, formEditDem);
        } else {
            const tables = document.querySelectorAll('.dt-wrap');
            if (tables.length > 0) {
                tables[0].parentNode.insertBefore(div, tables[0]);
            }
        }

        return div;
    }

    updateFormFields() {
        if (!this.location || !this.rules) return;

        // Mettre à jour les valeurs min/max du champ montant
        const montantInput = document.querySelector('#a-montant');
        if (montantInput) {
            montantInput.min = this.rules.min_montant;
            montantInput.max = this.rules.max_montant;
            montantInput.placeholder = `${this.rules.min_montant} - ${this.rules.max_montant} ${this.location.currency}`;
        }

        // Mettre à jour la durée maximale
        const dureeInput = document.querySelector('#a-duree');
        if (dureeInput) {
            dureeInput.max = this.rules.duree_max_mois;
        }

        // Mettre à jour le taux d'intérêt par défaut
        const tauxInput = document.querySelector('#a-taux');
        if (tauxInput && !tauxInput.value) {
            tauxInput.value = this.rules.taux_defaut;
        }

        // Ajouter le champ de devise caché dans le formulaire d'édition
        const formEditDem = document.getElementById('form-edit-dem');
        if (formEditDem) {
            let currencyInput = formEditDem.querySelector('input[name="currency"]');
            if (!currencyInput) {
                currencyInput = document.createElement('input');
                currencyInput.type = 'hidden';
                currencyInput.name = 'currency';
                currencyInput.value = this.location.currency;
                formEditDem.appendChild(currencyInput);
            } else {
                currencyInput.value = this.location.currency;
            }

            // Ajouter country_code caché
            let countryInput = formEditDem.querySelector('input[name="country_code"]');
            if (!countryInput) {
                countryInput = document.createElement('input');
                countryInput.type = 'hidden';
                countryInput.name = 'country_code';
                countryInput.value = this.location.country_code;
                formEditDem.appendChild(countryInput);
            } else {
                countryInput.value = this.location.country_code;
            }
        }
    }

    setupMontantListener() {
        const montantInput = document.querySelector('#a-montant');
        if (montantInput) {
            montantInput.addEventListener('input', () => {
                this.validateAndHighlight();
            });
        } else {
            // Si le formulaire n'existe pas encore, attendre quelques ms et réessayer
            setTimeout(() => this.setupMontantListener(), 500);
        }
    }

    setupSimulationListener() {
        // Ajouter un bouton de simulation
        const formEditDem = document.getElementById('form-edit-dem');
        if (formEditDem) {
            let simBtn = formEditDem.querySelector('.btn-simulation');
            if (!simBtn && this.location) {
                simBtn = document.createElement('button');
                simBtn.type = 'button';
                simBtn.className = 'btn-simulation';
                simBtn.textContent = '💰 Simuler le prêt';
                simBtn.style.cssText = 'padding:0.6rem 1rem; background:linear-gradient(135deg, #3b82f6, #8b5cf6); color:white; border:none; border-radius:0.4rem; cursor:pointer; font-weight:500; margin:0.5rem 0 0 0;';
                
                const buttonsDiv = formEditDem.querySelector('div[style*="display:flex;gap"]') || formEditDem.lastElementChild;
                if (buttonsDiv) {
                    buttonsDiv.appendChild(simBtn);
                }
                
                simBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.simulateLoan();
                });
            }
        } else {
            // Si le formulaire n'existe pas encore, réessayer après un délai
            setTimeout(() => this.setupSimulationListener(), 500);
        }
    }

    validateAndHighlight() {
        if (!this.rules) return;

        const montantInput = document.querySelector('#a-montant');
        const montant = parseFloat(montantInput?.value) || 0;

        if (montant && (montant < this.rules.min_montant || montant > this.rules.max_montant)) {
            montantInput.style.borderColor = 'var(--rose)';
            montantInput.style.borderWidth = '2px';
        } else {
            montantInput.style.borderColor = '';
            montantInput.style.borderWidth = '';
        }
    }

    async simulateLoan() {
        const montantInput = document.querySelector('#a-montant');
        const montant = parseFloat(montantInput?.value) || 0;

        console.log('[Simulation] Montant:', montant, 'Min:', this.rules?.min_montant, 'Max:', this.rules?.max_montant);

        if (!montant || montant < this.rules.min_montant || montant > this.rules.max_montant) {
            alert('❌ Montant invalide pour votre région\n\nLimites: ' + this.rules.min_montant + ' - ' + this.rules.max_montant + ' ' + this.location.currency);
            return;
        }

        try {
            const controllerPath = window.CONTROLLER_PATH || '/controller/AdminCreditController.php';
            const formData = new FormData();
            formData.append('action', 'get_loan_simulation');
            formData.append('montant', montant);

            console.log('[Simulation] Posting to:', controllerPath);

            const response = await fetch(controllerPath, {
                method: 'POST',
                body: formData
            });

            console.log('[Simulation] Response status:', response.status);

            const responseText = await response.text();
            console.log('[Simulation] Raw response:', responseText);

            const data = JSON.parse(responseText);

            if (data.error) {
                alert('❌ ' + data.error);
                return;
            }

            const msg = `💰 SIMULATION DE PRÊT
━━━━━━━━━━━━━━━━━━━━━━━━━
📍 Région: ${this.location.country_name}
💵 Montant demandé: ${data.montant} ${data.currency}
📊 Taux annuel: ${data.taux_annuel}%
📅 Durée du prêt: ${data.duree_mois} mois
━━━━━━━━━━━━━━━━━━━━━━━━━
💳 Mensualité: ${data.mensualite} ${data.currency}
🎯 Coût des intérêts: ${data.frais_interets} ${data.currency}
💰 Montant total à rembourser: ${data.total_avec_interets} ${data.currency}
━━━━━━━━━━━━━━━━━━━━━━━━━
📆 Date fin prévue: ${data.date_fin_prevue}`;

            alert(msg);
        } catch (error) {
            console.error('[Simulation] Erreur:', error);
            console.error('[Simulation] Stack:', error.stack);
            alert('❌ Erreur lors de la simulation');
        }
    }

    getCountryFlag(countryCode) {
        // Convertir les codes pays en emojis de drapeaux
        const flags = {
            'TN': '🇹🇳',
            'FR': '🇫🇷',
            'US': '🇺🇸',
            'GB': '🇬🇧',
            'DE': '🇩🇪',
            'IT': '🇮🇹',
            'ES': '🇪🇸',
            'CH': '🇨🇭',
            'CA': '🇨🇦',
            'BE': '🇧🇪',
            'DZ': '🇩🇿',
            'MA': '🇲🇦',
            'LY': '🇱🇾',
        };
        return flags[countryCode] || '🌍';
    }
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Geoloc] DOMContentLoaded - Initializing CreditFormGeolocation...');
    console.log('[Geoloc] Controller path available:', window.CONTROLLER_PATH);
    new CreditFormGeolocation();
});
