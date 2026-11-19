<?php
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

$user_id = $_SESSION['user_id'];

// Ambil info sesi ujian terakhir untuk memastikan data valid
$stmtSession = $pdo->prepare("
    SELECT es.id as session_id, es.status_siswa, es.selesai_ujian, e.nama_ujian
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    WHERE es.user_id = ?
    ORDER BY es.id DESC LIMIT 1
");
$stmtSession->execute([$user_id]);
$session = $stmtSession->fetch();

if (!$session) {
    header("Location: dashboard.php");
    exit;
}

if ($session['status_siswa'] === 'pengerjaan') {
    finalize_exam_session($pdo, $session['session_id']);
    $session['status_siswa'] = 'selesai';
}

// Format waktu selesai jika diperlukan (waktu saat ini atau dari DB)
$waktu_selesai = isset($session['selesai_ujian']) ? date('d M Y, H:i', strtotime($session['selesai_ujian'])) : date('d M Y, H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Selesai - Sistem CBT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: min(100vh, #f4f7fe) linear-gradient(135deg, #f5f7ff 0%, #e4ecfa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1e293b;
        }

        .container {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 20px 40px -15px rgba(79, 70, 229, 0.12), 0 15px 25px -10px rgba(0, 0, 0, 0.04);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        /* Aksent dekoratif top bar */
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
        }

        /* Animasi wrapper untuk icon */
        .icon-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 28px;
            background: #ecfdf5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .icon-wrapper svg {
            width: 48px;
            height: 48px;
            color: #10b981;
        }

        /* Efek denyut di belakang icon */
        .icon-pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #10b981;
            opacity: 0.15;
            animation: pulse 2s infinite;
            z-index: 0;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 15px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
            padding: 0 8px;
        }

        .exam-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
        }

        /* Info info manifestasi data tersimpan */
        .info-card {
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 32px;
            text-align: left;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .info-item:not(:last-child) {
            border-bottom: 1px dashed #e2e8f0;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            color: #334155;
            font-weight: 600;
        }

        .status-tag {
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Button styling */
        .btn-action {
            display: block;
            width: 100%;
            background: #4f46e5;
            color: #ffffff;
            padding: 16px 24px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        /* Animasi Keyframes */
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.2; }
            70% { transform: scale(1.3); opacity: 0; }
            100% { transform: scale(1); opacity: 0; }
        }

        /* Responsive Mobile optimization */
        @media (max-width: 480px) {
            body {
                padding: 16px;
                background: #ffffff; /* Seamless full screen on tiny devices */
                align-items: flex-start;
                padding-top: 40px;
            }

            .container {
                box-shadow: none;
                border: none;
                padding: 24px 12px;
            }

            h1 {
                font-size: 22px;
            }

            .subtitle {
                font-size: 14px;
                margin-bottom: 24px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="icon-wrapper">
            <div class="icon-pulse"></div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h1>Ujian Selesai!</h1>
        <p class="subtitle">
            Terima kasih telah berpartisipasi dalam pengerjaan lembar ujian daring ini.
            <br>
            <span class="exam-badge"><?= htmlspecialchars($session['nama_ujian']) ?></span>
        </p>

        <div class="info-card">
            <div class="info-item">
                <span class="info-label">Status Lembar Jawab</span>
                <span class="status-tag">Tersimpan</span>
            </div>
            <div class="info-item">
                <span class="info-label">Waktu Penyelesaian</span>
                <span class="info-value"><?= $waktu_selesai ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Kalkulasi Nilai</span>
                <span class="info-value" style="color: #10b981; font-weight: 700;">Sistem Mengunci</span>
            </div>
        </div>

        <a href="dashboard.php" class="btn-action">Kembali ke Dashboard</a>
    </div>

</body>
</html>