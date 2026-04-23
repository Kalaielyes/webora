document.addEventListener('DOMContentLoaded', () => {
    const inputs = {
        payee: document.getElementById('payee'),
        amountWords: document.getElementById('amount_words'),
        amountDigits: document.getElementById('amount_digits'),
        cin: document.getElementById('cin'),
        rib: document.getElementById('rib'),
        date: document.getElementById('date'),
        signature: document.getElementById('agence') // Using agence for signature label in this demo
    };

    const displays = {
        payee: document.querySelector('.check-body .check-line:nth-child(1) .line-value'),
        amountWords: document.querySelector('.check-body .check-line:nth-child(2) .line-value'),
        amountDigits: document.querySelector('.box-value.amount'),
        cin: document.querySelector('.box-value.cin'),
        rib: document.querySelector('.box-value.rib'),
        date: document.querySelector('.check-meta .date-val'),
        signature: document.querySelector('.signature-val'),
        agence: document.querySelector('.check-meta .agence-val')
    };

    // Initial sync
    const sync = (key) => {
        if (!inputs[key] || !displays[key]) return;
        
        let val = inputs[key].value;
        if (key === 'amountDigits') {
            val = val ? parseFloat(val).toLocaleString('fr-FR', { minimumFractionDigits: 3 }) : '0,000';
        }
        
        displays[key].textContent = val || (key === 'amountDigits' ? '0,000' : '—');
        
        // Special case for agence
        if (key === 'signature') {
            displays['agence'].textContent = val || '—';
        }
    };

    Object.keys(inputs).forEach(key => {
        inputs[key].addEventListener('input', () => sync(key));
    });

    // Close button functionality
    document.querySelector('.close-btn').addEventListener('click', () => {
        window.location.href = 'backoffice_chequier.php';
    });
});
