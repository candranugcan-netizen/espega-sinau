<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #467ee5; 
            --primary-light: #81c8f8;
            --secondary: #0ea5e9;
            --bg-gradient: linear-gradient(135deg, #66afea 0%, #1f91ee 100%);
            --white: #ffffff;
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Poppins', sans-serif; 
        }

        body { 
            background: #f3f4f6;
            background-image: 
                radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.1) 0, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(14, 165, 233, 0.1) 0, transparent 50%);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }

        .login-container {
            width: 100%;
            max-width: 900px;
            display: flex;
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Sisi Kiri: Visual/Branding (Tersembunyi di Mobile Kecil) */
        .login-sidebar {
            flex: 1;
            background: var(--bg-gradient);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: var(--white);
            text-align: center;
        }

        .login-sidebar i {
            font-size: 80px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }

        .login-sidebar h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-sidebar p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Sisi Kanan: Form */
        .login-wrapper { 
            flex: 1;
            padding: 50px 40px; 
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header { text-align: left; margin-bottom: 35px; }
        .header h2 { color: #1f2937; font-weight: 700; font-size: 24px; margin-bottom: 8px; }
        .header p { font-size: 14px; color: #6b7280; }

        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-size: 13px; 
            font-weight: 600; 
            color: #4b5563; 
            margin-left: 4px;
        }
        
        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 15px;
            color: #9ca3af;
            font-size: 16px;
        }

        .form-group input { 
            width: 100%; 
            padding: 14px 14px 14px 45px; 
            border: 2px solid #f3f4f6; 
            border-radius: 12px; 
            outline: none; 
            transition: all 0.3s ease;
            background: #f9fafb;
            font-size: 14px;
        }

        .form-group input:focus { 
            border-color: var(--primary-light); 
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn-primary { 
            width: 100%; 
            padding: 14px; 
            background: var(--bg-gradient); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 600; 
            font-size: 16px;
            cursor: pointer; 
            margin-top: 10px; 
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .btn-primary:active { transform: translateY(0); }

        .alert { 
            padding: 12px 16px; 
            border-radius: 12px; 
            font-size: 13px; 
            margin-bottom: 25px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fef2f2; 
            color: #b91c1c; 
            border: 1px solid #fee2e2;
        }

        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Footer info */
        .footer-text {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .login-container {
                max-width: 450px;
                flex-direction: column;
            }
            .login-sidebar {
                padding: 30px 20px;
            }
            .login-sidebar i { font-size: 50px; margin-bottom: 10px; }
            .login-sidebar h1 { font-size: 22px; }
            .login-sidebar p { display: none; }
            
            .login-wrapper {
                padding: 35px 25px;
            }
            .header { text-align: center; }
        }

        @media (max-width: 480px) {
            body { padding: 15px; }
            .header h2 { font-size: 20px; }
            .form-group label { font-size: 12px; }
        }

        /* Posisi khusus untuk ikon mata (toggle password) */
        .input-icon-wrapper .toggle-password {
            position: absolute;
            right: 15px;      /* Jarak dari kanan */
            left: auto;       /* Menimpa aturan left jika ada di ikon sebelumnya */
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;   /* Warna default ikon */
            transition: color 0.3s ease;
        }

        .input-icon-wrapper .toggle-password:hover {
            color: var(--primary); /* Berubah warna saat di-hover */
        }

        /* Pastikan padding input cukup di sebelah kanan agar teks tidak tertutup ikon */
        .input-icon-wrapper input {
            padding-right: 40px; 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-sidebar">
            <i class="fas fa-user-graduate"></i>
            <h1>Espega Sinau</h1>
            <p>Selamat Datang di Sistem Ujian Digital SMPN 3 Ngawen. Tingkatkan kejujuran dan prestasi melalui teknologi.</p>
        </div>

        <div class="login-wrapper">
            <div class="header">
                <h2>Silakan Login</h2>
                <p>Gunakan Username & Token Akses Anda</p>
            </div>
            
            <?php if(isset($_GET['pesan']) && $_GET['pesan'] === 'sesi_habis'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    Sesi login Anda telah habis. Silakan login kembali.
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="controllers/AuthController.php" method="POST">
                <div class="form-group">
                    <label>Username / NIS</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Masukkan ID Anda" required autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label>Token / Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-key icon-kiri"></i> <input type="password" name="token" id="tokenInput" placeholder="Masukkan Kode Akses" required>
                        <i class="fas fa-eye toggle-password" id="toggleToken"></i>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-primary">
                    <span>Masuk</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer-text">
                &copy; <?php echo date('Y'); ?> Espega Sinau - All Rights Reserved
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggleToken = document.getElementById('toggleToken');
        const tokenInput = document.getElementById('tokenInput');

        toggleToken.addEventListener('click', function () {
            // Toggle tipe input antara 'password' dan 'text'
            const type = tokenInput.getAttribute('type') === 'password' ? 'text' : 'password';
            tokenInput.setAttribute('type', type);
            
            // Toggle ikon antara mata terbuka dan mata dicoret
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
    </script>
</body>
</html>