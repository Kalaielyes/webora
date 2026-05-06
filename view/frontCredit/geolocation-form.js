/**
 * Géolocalisation et adaptation du formulaire de crédit
 * À ajouter dans front_credit.php
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
    }

    async fetchGeolocation() {
        try {
            const response = await fetch('/controller/CreditController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_client_location'
            });

            const data = await response.json();
            this.location = data.location;
            this.rules = data.rules;

            // Afficher les infos de géolocalisation
            this.displayGeoInfo();
        } catch (error) {
            console.error('Erreur géolocalisation:', error);
        }
    }

    displayGeoInfo() {
        if (!this.location) return;

        const infoDiv = document.getElementById('geo-info') || this.createGeoInfoDiv();

        infoDiv.innerHTML = `
            <div class="geo-badge">
                <span class="geo-flag">${this.getCountryFlag(this.location.country_code)}</span>
                <div class="geo-details">
                    <strong>${this.location.country_name}</strong>
                    <small>${this.location.city ? this.location.city + ', ' : ''}${this.location.region}</small>
                    <div class="geo-currency">
                        <strong>${this.location.currency}</strong> - ${this.location.currency_symbol}
                    </div>
                </div>
            </div>
        `;
    }

    createGeoInfoDiv() {
        const div = document.createElement('div');
        div.id = 'geo-info';
        div.className = 'geo-info-box';

        // Insérer avant le formulaire
        const form = document.querySelector('form');
        if (form) {
            form.parentNode.insertBefore(div, form);
        }

        return div;
    }

    updateFormFields() {
        if (!this.location || !this.rules) return;

        // Mettre à jour les valeurs min/max du champ montant
        const montantInput = document.querySelector('input[name="montant"]');
        if (montantInput) {
            montantInput.min = this.rules.min_montant;
            montantInput.max = this.rules.max_montant;
            montantInput.placeholder = `${this.rules.min_montant} - ${this.rules.max_montant} ${this.location.currency}`;
        }

        // Mettre à jour la durée maximale
        const dureeInput = document.querySelector('input[name="duree_mois"]');
        if (dureeInput) {
            dureeInput.max = this.rules.duree_max_mois;
        }

        // Ajouter le champ de devise caché
        let currencyInput = document.querySelector('input[name="currency"]');
        if (!currencyInput) {
            currencyInput = document.createElement('input');
            currencyInput.type = 'hidden';
            currencyInput.name = 'currency';
            document.querySelector('form').appendChild(currencyInput);
        }
        currencyInput.value = this.location.currency;

        // Ajouter le code pays caché
        let countryInput = document.querySelector('input[name="country_code"]');
        if (!countryInput) {
            countryInput = document.createElement('input');
            countryInput.type = 'hidden';
            countryInput.name = 'country_code';
            document.querySelector('form').appendChild(countryInput);
        }
        countryInput.value = this.location.country_code;
    }

    setupMontantListener() {
        const montantInput = document.querySelector('input[name="montant"]');
        if (!montantInput) return;

        montantInput.addEventListener('input', (e) => {
            this.updateSimulation(parseFloat(e.target.value));
        });
    }

    async updateSimulation(montant) {
        if (!montant || montant <= 0 || !this.location) return;

        try {
            const response = await fetch('/controller/CreditController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_loan_simulation&montant=${montant}&country_code=${this.location.country_code}`
            });

            const simulation = await response.json();

            if (!simulation.error) {
                this.displaySimulation(simulation);
            }
        } catch (error) {
            console.error('Erreur simulation:', error);
        }
    }

    displaySimulation(simulation) {
        let simDiv = document.getElementById('loan-simulation');
        if (!simDiv) {
            simDiv = document.createElement('div');
            simDiv.id = 'loan-simulation';
            simDiv.className = 'simulation-box';
            document.querySelector('form')?.parentNode.appendChild(simDiv);
        }

        simDiv.innerHTML = `
            <div class="simulation-title">📊 Simulation de crédit</div>
            <div class="simulation-grid">
                <div class="sim-item">
                    <span class="sim-label">Montant demandé</span>
                    <strong>${this.formatCurrency(simulation.montant, simulation.currency_symbol)}</strong>
                </div>
                <div class="sim-item">
                    <span class="sim-label">Taux annuel</span>
                    <strong>${simulation.taux_annuel.toFixed(2)}%</strong>
                </div>
                <div class="sim-item">
                    <span class="sim-label">Durée</span>
                    <strong>${simulation.duree_mois} mois</strong>
                </div>
                <div class="sim-item highlight">
                    <span class="sim-label">Mensualité</span>
                    <strong>${this.formatCurrency(simulation.mensualite, simulation.currency_symbol)}</strong>
                </div>
                <div class="sim-item">
                    <span class="sim-label">Total intérêts</span>
                    <strong style="color: #d32f2f;">${this.formatCurrency(simulation.frais_interets, simulation.currency_symbol)}</strong>
                </div>
                <div class="sim-item">
                    <span class="sim-label">Coût total</span>
                    <strong>${this.formatCurrency(simulation.total_avec_interets, simulation.currency_symbol)}</strong>
                </div>
            </div>
            <div class="simulation-info">
                Date de fin prévue: <strong>${new Date(simulation.date_fin_prevue).toLocaleDateString('fr-FR')}</strong>
            </div>
        `;
    }

    formatCurrency(amount, symbol) {
        return `${amount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${symbol}`;
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
    new CreditFormGeolocation();
});
