<?php
$lines = file('views/admin/kelola_ujian.php');
foreach ($lines as $num => $line) {
    if (stripos($line, 'soal') !== false) {
        echo ($num + 1) . ": " . htmlspecialchars(trim($line)) . "<br>";
    }
}
unlink(__FILE__);
