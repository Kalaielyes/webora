/**
 * Chequier Workflow - LegaFin Backoffice
 * Consolidated JS logic for managing chequiers and cheques
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Chequier Workflow Initialized');
});

/**
 * Open Modal for creating/editing chequier
 * @param {Object} data - Optional data for editing
 */
function openChequierModal(data = null) {
    // Logic to open a modal (to be integrated with a shared modal component if available)
    alert(data ? 'Modifier Chéquier' : 'Nouveau Chéquier');
}

/**
 * Handle Chequier Deletion
 * @param {number} id 
 */
function deleteChequier(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce chéquier ?')) {
        fetch(`../../api/chequier-api.php/chequier/delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_chequier: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
            }
        });
    }
}

/**
 * Update Demand Status
 * @param {number} id 
 * @param {string} status 
 */
function updateDemandStatus(id, status) {
    fetch(`../../api/chequier-api.php/demande-chequier/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_demande: id, statut: status })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de mettre à jour'));
        }
    });
}
