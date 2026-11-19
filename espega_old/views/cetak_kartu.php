<?php
require_once '../config/database.php';
// Ambil semua data siswa
$stmt = $pdo->query("SELECT username, password, nama_lengkap FROM users WHERE role_id = 3");
$siswa = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Kartu Ujian</title>
    <style>
        .kartu { border: 2px solid #000; width: 300px; padding: 15px; margin: 10px; float: left; font-family: Arial; }
        .header { text-align: center; border-bottom: 1px solid #000; margin-bottom: 10px; }
    </style>
</head>
<body onload="window.print()"> <?php foreach($siswa as $s): ?>
    <div class="kartu">
        <div class="header">
            <strong>KARTU UJIAN CBT</strong><br>
            SMK NEGERI CONTOH
        </div>
        <table>
            <tr><td>Nama</td><td>: <?php echo $s['nama_lengkap']; ?></td></tr>
            <tr><td>NIS</td><td>: <?php echo $s['username']; ?></td></tr>
            <tr><td><b>TOKEN</b></td><td>: <strong><?php echo $s['password']; ?></strong></td></tr>
        </table>
        <p style="font-size: 10px; margin-top: 10px;">*Gunakan NIS & Token untuk Login. Dilarang keluar browser selama ujian!</p>
    </div>
    <?php endforeach; ?>
</body>
</html>