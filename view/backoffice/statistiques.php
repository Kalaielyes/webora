<?php
// Suppression de la logique de récupération des statistiques
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Demandes de Chéquiers</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="chequier.css?v=<?= time() ?>">
</head>
<body>
<div class="main">
    <h1>Statistiques des Demandes de Chéquiers</h1>
    <canvas id="statsChart" width="400" height="200"></canvas>
</div>
<script>
    const ctx = document.getElementById('statsChart').getContext('2d');

    // Récupération des données depuis le fichier statistiques_logic.php
    fetch('statistiques_logic.php')
        .then(response => response.json())
        .then(statsData => {
            const labels = Object.keys(statsData);
            const acceptedData = labels.map(date => statsData[date]['acceptées']);
            const refusedData = labels.map(date => statsData[date]['refusées']);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Demandes Acceptées',
                            data: acceptedData,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 1
                        },
                        {
                            label: 'Demandes Refusées',
                            data: refusedData,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Statistiques des Demandes de Chéquiers par Jour'
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Erreur lors de la récupération des données :', error));
</script>
</body>
</html>