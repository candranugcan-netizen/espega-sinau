<?php 
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'laporan';
$page_title = 'Laporan Hasil Ujian';

$stmt = $pdo->query("SELECT u.username, u.nama_lengkap, u.kelas, es.skor, es.status_siswa, es.selesai_ujian 
                     FROM exam_sessions es 
                     JOIN users u ON es.user_id = u.id 
                     ORDER BY u.kelas ASC, es.skor DESC");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$total_peserta = count($results);
$total_selesai = 0;
$total_terblokir = 0;
$total_skor = 0;
foreach ($results as $r) {
    if ($r['status_siswa'] === 'selesai') $total_selesai++;
    if ($r['status_siswa'] === 'terblokir') $total_terblokir++;
    $total_skor += (float)$r['skor'];
}
$rata_rata = $total_peserta > 0 ? round($total_skor / $total_peserta, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hasil Ujian - Admin Panel - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin_theme.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        .page-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 3px;
        }

        .card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .table-responsive {
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }
        thead {
            background: var(--adm-slate-50);
            border-bottom: 2px solid var(--border-color);
        }
        th {
            padding: 13px 16px;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--adm-slate-600);
            font-weight: 700;
            white-space: nowrap;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--adm-slate-100);
            vertical-align: middle;
            color: var(--text-main);
        }
        tr:last-child td {
            border-bottom: none;
        }
        tbody tr:hover {
            background-color: #fbfcfe;
        }

        .badge-kelas {
            display: inline-block;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 3px 8px;
            border-radius: 4px;
            background: var(--adm-slate-100);
            color: var(--adm-slate-800);
            border: 1px solid var(--border-color);
        }

        .student-nis {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--adm-slate-700);
            font-size: 0.82rem;
            background: var(--adm-slate-50);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
        }

        .score-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--primary);
            background: var(--adm-blue-50);
            border: 1px solid var(--adm-blue-100);
            padding: 3px 10px;
            border-radius: 6px;
            min-width: 48px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .status-badge.selesai {
            background: var(--adm-success-bg);
            color: #065f46;
            border: 1px solid var(--adm-success-border);
        }
        .status-badge.terblokir {
            background: var(--adm-danger-bg);
            color: #991b1b;
            border: 1px solid var(--adm-danger-border);
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-icon {
            font-size: 3rem;
            color: var(--adm-slate-400);
            margin-bottom: 12px;
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <div class="page-header">
        <div>
            <h1>Laporan Hasil Ujian Siswa</h1>
            <p>Rekapitulasi nilai dan integritas pelaksanaan ujian seluruh kelas</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="../../controllers/ExportController.php" class="btn btn-primary">
                <i class="fa-solid fa-file-excel"></i>
                <span>Unduh Format Excel</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_peserta) ?></div>
                <div class="adm-stat-label">Total Peserta</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_selesai) ?></div>
                <div class="adm-stat-label">Selesai Ujian</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-danger);">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="adm-stat-num" style="color: <?= ($total_terblokir > 0) ? 'var(--adm-danger)' : 'inherit' ?>;"><?= number_format($total_terblokir) ?></div>
                <div class="adm-stat-label">Terdeteksi / Terblokir</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $rata_rata ?></div>
                <div class="adm-stat-label">Rata-Rata Nilai</div>
            </div>
        </div>
    </div>

    <div class="card">
        <?php if (empty($results)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-clipboard-question empty-icon"></i>
                <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 6px;">Belum Ada Data Hasil Ujian</h3>
                <p>Data hasil ujian siswa akan otomatis tercatat di sini setelah siswa menyelesaikan ujian.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Kelas</th>
                            <th style="width: 140px;">NIS Siswa</th>
                            <th>Nama Lengkap</th>
                            <th style="width: 120px; text-align: center;">Skor Nilai</th>
                            <th style="width: 150px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $row): ?>
                        <tr>
                            <td><span class="badge-kelas"><?= htmlspecialchars($row['kelas']) ?></span></td>
                            <td><span class="student-nis"><?= htmlspecialchars($row['username']) ?></span></td>
                            <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                            <td style="text-align: center;">
                                <span class="score-pill"><?= htmlspecialchars($row['skor']) ?></span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($row['status_siswa'] === 'terblokir'): ?>
                                    <span class="status-badge terblokir">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Terdeteksi
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge selesai">
                                        <i class="fa-solid fa-circle-check"></i> Selesai
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/footer.php'; ?>