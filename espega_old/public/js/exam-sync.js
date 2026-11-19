function simpanJawaban(soalId, jawaban) {
    const data = { soal_id: soalId, pilihan: jawaban };
    fetch('../../controllers/SiswaController.php?action=auto_save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        console.log("Auto-saved:", res.status);
    });
}