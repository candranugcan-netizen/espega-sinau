<?php 
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

date_default_timezone_set('Asia/Jakarta');

$active_nav = 'monitoring';
$page_title = 'Live Monitoring Pelanggaran';

// Data awal untuk statistik server-side
$totalSiswa = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
$totalBlokir = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3 AND status_blokir = '1'")->fetchColumn();
$totalAktif = $totalSiswa - $totalBlokir;
$totalSedangUjian = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM exam_sessions WHERE status_siswa = 'pengerjaan'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Pelanggaran - Admin Panel - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        .page-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        /* Live indicator */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            border-radius: 9999px;
            background: var(--adm-success-bg);
            border: 1px solid var(--adm-success-border);
            font-size: 0.8rem;
            font-weight: 700;
            color: #065f46;
            letter-spacing: 0.03em;
        }
        .live-dot {
            width: 8px;
            height: 8px;
            background: var(--adm-success);
            border-radius: 50%;
            display: inline-block;
            animation: livePulse 1.6s infinite;
        }
        @keyframes livePulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Info Banner */
        .info-banner {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.86rem;
            color: var(--adm-slate-700);
            box-shadow: var(--shadow-sm);
        }
        .info-banner i {
            font-size: 1.15rem;
            color: var(--primary);
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* Card */
        .card {
            background: #ffffff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .search-box input {
            padding: 8px 14px 8px 36px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.88rem;
            background: var(--adm-slate-50);
            color: var(--text-main);
            width: 280px;
            transition: all 0.2s;
        }
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            background: #ffffff;
        }
        .search-box .search-icon {
            position: absolute;
            left: 12px;
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .result-count {
            font-size: 0.84rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .result-count strong {
            color: var(--text-main);
        }

        .refresh-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--adm-slate-700);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .refresh-btn:hover {
            background: var(--adm-slate-50);
            border-color: var(--adm-slate-400);
            color: var(--primary);
        }
        .refresh-btn.spinning i {
            animation: spin 0.6s linear;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Table */
        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
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
            padding: 13px 16px;
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

        /* User Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            background: var(--adm-slate-100);
            color: var(--primary);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.82rem;
            flex-shrink: 0;
        }
        .user-info {
            display: flex;
            flex-direction: column;
        }
        .user-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.9rem;
        }
        .user-nis {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .badge-kelas {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            background: var(--adm-slate-100);
            color: var(--adm-slate-800);
            border: 1px solid var(--border-color);
        }

        .badge-blocked {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 700;
            background: var(--adm-danger-bg);
            color: #991b1b;
            border: 1px solid var(--adm-danger-border);
            letter-spacing: 0.04em;
        }

        .btn-unblock {
            padding: 6px 14px;
            font-size: 0.8rem;
            background: #ffffff;
            color: var(--adm-slate-800);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-unblock:hover {
            background: var(--adm-blue-50);
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.15);
        }

        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-icon {
            font-size: 3rem;
            color: var(--adm-success);
            margin-bottom: 14px;
            display: inline-block;
        }
        .empty-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }
        .empty-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            max-width: 440px;
            margin: 0 auto;
            line-height: 1.5;
        }

        .last-updated {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Live Monitoring Pelanggaran Siswa</h1>
            <p>Pantau dan kelola siswa yang terblokir secara otomatis akibat pelanggaran saat ujian. Sistem sinkron otomatis setiap 3 detik.</p>
        </div>
        <div>
            <div class="live-indicator">
                <span class="live-dot"></span>
                <span>MONITORING AKTIF</span>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card" id="statBlokir">
            <div class="adm-stat-icon" style="color: var(--adm-danger);">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <div class="adm-stat-num" id="statBlokirNum" style="color: <?= ($totalBlokir > 0) ? 'var(--adm-danger)' : 'inherit' ?>;"><?= $totalBlokir ?></div>
                <div class="adm-stat-label">Siswa Terblokir</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalSiswa) ?></div>
                <div class="adm-stat-label">Total Seluruh Siswa</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-success);">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalAktif) ?></div>
                <div class="adm-stat-label">Siswa Aktif</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $totalSedangUjian ?></div>
                <div class="adm-stat-label">Sedang Mengerjakan</div>
            </div>
        </div>
    </div>

    <!-- Info Banner -->
    <div class="info-banner">
        <i class="fa-solid fa-circle-info"></i>
        <div>
            <strong>Mekanisme Blokir Otomatis:</strong> Siswa otomatis terblokir ketika berpindah tab browser, keluar dari aplikasi, atau membuka jendela lain selama ujian berlangsung. Klik tombol <strong>Buka Blokir</strong> untuk memberikan izin kepada siswa agar dapat melanjutkan pengerjaan.
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card">
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Cari NIS atau nama siswa..." oninput="filterTable()" autocomplete="off">
                </div>
                <div class="result-count" id="resultCount">
                    Memuat data...
                </div>
            </div>
            <div class="toolbar-right">
                <button type="button" class="refresh-btn" id="refreshBtn" onclick="manualRefresh()" title="Refresh Sekarang">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
                <a href="siswa.php?status_filter=blokir" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>Buka di Data Siswa</span>
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>Identitas Siswa</th>
                        <th style="text-align: center; width: 110px;">Kelas</th>
                        <th style="text-align: center; width: 140px;">Status</th>
                        <th style="text-align: center; width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="blocked-list-container">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 8px;"></i><br>
                            Memuat data siswa terblokir...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Last Updated -->
        <div class="last-updated" id="lastUpdated">
            <i class="fa-solid fa-clock"></i>
            <span>Menunggu pembaruan data...</span>
        </div>
    </div>

    <script>
    let allBlockedData = [];

    function getInitials(name) {
        const words = name.trim().split(/\s+/);
        if (words.length >= 2) return (words[0][0] + words[1][0]).toUpperCase();
        if (words.length === 1 && words[0].length > 0) return words[0].substring(0, 2).toUpperCase();
        return 'SW';
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function formatTime(date) {
        return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function renderTable(data) {
        const container = document.getElementById('blocked-list-container');
        const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();

        let filtered = data;
        if (searchTerm) {
            filtered = data.filter(s =>
                s.nama_lengkap.toLowerCase().includes(searchTerm) ||
                s.username.toLowerCase().includes(searchTerm)
            );
        }

        const countEl = document.getElementById('resultCount');
        if (data.length === 0) {
            countEl.innerHTML = '<strong>0</strong> siswa terblokir';
        } else if (searchTerm && filtered.length !== data.length) {
            countEl.innerHTML = `Menampilkan <strong>${filtered.length}</strong> dari <strong>${data.length}</strong> siswa terblokir`;
        } else {
            countEl.innerHTML = `<strong>${data.length}</strong> siswa terblokir ditemukan`;
        }

        document.getElementById('statBlokirNum').textContent = data.length;
        const statBlokirNum = document.getElementById('statBlokirNum');
        if (data.length > 0) {
            statBlokirNum.style.color = 'var(--adm-danger)';
        } else {
            statBlokirNum.style.color = 'inherit';
        }

        if (filtered.length === 0) {
            if (data.length === 0) {
                container.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fa-solid fa-circle-check empty-icon"></i>
                                <div class="empty-title">Semua Siswa Aman!</div>
                                <div class="empty-desc">Tidak ada siswa yang terblokir saat ini. Semua siswa dalam status aktif dan dapat mengikuti ujian secara normal.</div>
                            </div>
                        </td>
                    </tr>`;
            } else {
                container.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state" style="padding: 40px;">
                                <i class="fa-solid fa-magnifying-glass" style="font-size: 2rem; color: var(--adm-slate-400); margin-bottom: 10px;"></i>
                                <div class="empty-title" style="color: var(--text-muted); font-size: 1rem;">Tidak ditemukan</div>
                                <div class="empty-desc">Tidak ada siswa terblokir yang cocok dengan kata kunci "<strong>${escapeHtml(searchTerm)}</strong>".</div>
                            </div>
                        </td>
                    </tr>`;
            }
            return;
        }

        let html = '';
        filtered.forEach((s, index) => {
            const initials = getInitials(s.nama_lengkap);
            html += `
                <tr>
                    <td style="text-align: center; color: var(--text-muted); font-weight: 600; font-size: 0.82rem;">
                        ${index + 1}
                    </td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar">${initials}</div>
                            <div class="user-info">
                                <span class="user-name">${escapeHtml(s.nama_lengkap)}</span>
                                <span class="user-nis">NIS: ${escapeHtml(s.username)}</span>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge-kelas">${escapeHtml(s.kelas || '-')}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge-blocked">
                            <i class="fa-solid fa-triangle-exclamation"></i> TERBLOKIR
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-unblock" 
                                onclick="confirmUnblock(${s.id}, '${escapeHtml(s.nama_lengkap).replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-lock-open"></i> Buka Blokir
                        </button>
                    </td>
                </tr>`;
        });
        container.innerHTML = html;
    }

    function filterTable() {
        renderTable(allBlockedData);
    }

    function updateMonitoring(isManual = false) {
        fetch('get_blocked_students.php')
            .then(response => response.json())
            .then(data => {
                allBlockedData = data;
                renderTable(data);

                document.getElementById('lastUpdated').innerHTML = 
                    `<i class="fa-solid fa-clock"></i><span>Terakhir diperbarui: <strong>${formatTime(new Date())}</strong></span>`;

                if (isManual) {
                    const btn = document.getElementById('refreshBtn');
                    btn.classList.add('spinning');
                    setTimeout(() => btn.classList.remove('spinning'), 600);
                }
            })
            .catch(error => {
                console.error('Monitoring fetch error:', error);
                document.getElementById('lastUpdated').innerHTML = 
                    `<i class="fa-solid fa-triangle-exclamation" style="color: var(--adm-danger);"></i><span style="color: var(--adm-danger);">Gagal sinkron data. Mencoba ulang...</span>`;
            });
    }

    function manualRefresh() {
        updateMonitoring(true);
    }

    function confirmUnblock(id, nama) {
        Swal.fire({
            title: 'Buka Blokir Siswa?',
            html: `Apakah Anda yakin ingin membuka status blokir siswa:<br><br>
                   <strong style="font-size: 1.05rem; color: #0f172a;">${nama}</strong><br><br>
                   <span style="font-size: 0.85rem; color: #64748b;">Siswa akan dapat login dan melanjutkan ujian kembali.</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-lock-open"></i> Ya, Buka Blokir',
            cancelButtonText: 'Batal',
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Membuka blokir siswa...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                window.location.href = `../../controllers/AdminController.php?action=unblock&id=${id}`;
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'unblocked') {
            Swal.fire({
                icon: 'success',
                title: 'Blokir Berhasil Dibuka!',
                text: 'Siswa telah dapat login dan melanjutkan ujian.',
                confirmButtonColor: '#2563eb',
                timer: 3000,
                timerProgressBar: true
            });
            urlParams.delete('status');
            const newQ = urlParams.toString();
            window.history.replaceState({}, document.title, window.location.pathname + (newQ ? '?' + newQ : ''));
        }
    });

    updateMonitoring();
    setInterval(updateMonitoring, 3000);
    </script>

<?php include __DIR__ . '/footer.php'; ?>