// 1. Fungsi Fullscreen
function goFullScreen() {
    let elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    }
}

// 2. Deteksi Keluar Halaman/Pindah Tab
document.addEventListener("visibilitychange", function() {
    if (document.hidden) {
        // Jika siswa pindah tab, kirim log ke server
        logPelanggaran("Pindah Tab/Aplikasi");
    }
});

window.addEventListener("blur", function() {
    logPelanggaran("Browser Kehilangan Fokus");
});

function logPelanggaran(tipe) {
    fetch('../../controllers/SiswaController.php?action=log_pelanggaran', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'tipe=' + encodeURIComponent(tipe)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'blocked') {
            alert("Akses Ujian Anda Diblokir karena pelanggaran! Hubungi Pengawas.");
            window.location.reload();
        } else {
            alert("PERINGATAN: " + tipe + " terdeteksi! Kejadian dicatat oleh sistem.");
        }
    });
}

// 3. Blokir Klik Kanan & Shortcut Keyboard
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
    // Blokir F12, Ctrl+Shift+I, Ctrl+U, Ctrl+C, Ctrl+V
    if (e.keyCode == 123 || 
        (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74)) || 
        (e.ctrlKey && (e.keyCode == 85 || e.keyCode == 67 || e.keyCode == 86))) {
        e.preventDefault();
        alert("Fitur ini dinonaktifkan demi keamanan ujian.");
    }
});