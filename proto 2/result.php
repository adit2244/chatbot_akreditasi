<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = isset($_SESSION['results']) ? $_SESSION['results'] : [];
?>
<!DOCTYPE html>
<html>

<head>
    <title>Hasil Pencarian</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <h1>Hasil Pencarian</h1>
        <?php if (!empty($results)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($results[0]) as $key): ?>
                            <th><?= htmlspecialchars($key) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?= htmlspecialchars($value) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Grafik -->
            <canvas id="chart"></canvas>
            <script>
                const ctx = document.getElementById('chart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($results, 'nama_kegiatan')) ?>,
                        datasets: [{
                            label: 'Jumlah',
                            data: <?= json_encode(array_fill(0, count($results), 1)) ?>,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
        <?php else: ?>
            <p>Tidak ada data yang ditemukan.</p>
        <?php endif; ?>
    </div>
</body>

</html>