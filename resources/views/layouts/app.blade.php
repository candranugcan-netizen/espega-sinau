<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SMP Negeri 3 Ngawen - Espega Sinau')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f7fe',
                            100: '#ddf0fc',
                            200: '#bee1f9',
                            500: '#1495e7',
                            600: '#0c83ce', // Biru Logo Resmi SMPN 3 Ngawen
                            700: '#096cb0',
                            800: '#085990',
                            900: '#0c4a73',
                            dark: '#062c47',
                            gold: '#d97706', // Kuning Emas Padi Logo
                            navy: '#0f172a',
                            emerald: '#059669',
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
        }

        /* Standarisasi Modal Responsif Semua Layar (HP, Tablet, Desktop) */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            padding: 1rem;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.is-active,
        .modal-overlay.active,
        .modal-overlay.flex {
            display: flex !important;
        }

        .modal-dialog {
            position: relative;
            margin: auto;
            max-height: 90vh;
            max-height: 90dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
    @yield('body')

    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Peringatan',
            text: "{{ session('error') }}",
            confirmButtonColor: '#2563eb'
        });
    </script>
    @endif

    <!-- Universal Cross-Device Modal Engine (HP, Tablet, Laptop, Desktop) -->
    <script>
        window.openModal = function (id) {
            const modal = typeof id === 'string' ? document.getElementById(id) : id;
            if (!modal) return;

            modal.classList.remove('hidden');
            modal.classList.add('flex', 'is-active');
            modal.style.display = 'flex';
            document.body.classList.add('overflow-hidden');
            document.documentElement.classList.add('overflow-hidden');

            // Tambahkan listener klik backdrop untuk layar sentuh / mobile
            if (!modal.dataset.backdropBound) {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) {
                        window.closeModal(modal);
                    }
                });
                modal.dataset.backdropBound = 'true';
            }

            // Cegah bubbling jika klik di dalam konten modal
            const innerBox = modal.querySelector('.bg-white, .modal-dialog');
            if (innerBox && !innerBox.dataset.stopClickBound) {
                innerBox.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
                innerBox.dataset.stopClickBound = 'true';
            }

            // Autofocus input pertama yang valid (jika bukan perangkat mobile kecil)
            if (window.innerWidth >= 768) {
                setTimeout(() => {
                    const firstInput = modal.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
                    if (firstInput) firstInput.focus();
                }, 100);
            }
        };

        window.closeModal = function (id) {
            const modal = typeof id === 'string' ? document.getElementById(id) : id;
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex', 'is-active');
            modal.style.display = 'none';

            // Cek apakah masih ada modal lain yang terbuka sebelum merestore scroll body
            const anyOpen = Array.from(document.querySelectorAll('[id$="Modal"], .modal-overlay')).some(m => {
                return m !== modal && (m.style.display === 'flex' || m.classList.contains('is-active') || (!m.classList.contains('hidden') && window.getComputedStyle(m).display !== 'none'));
            });

            if (!anyOpen) {
                document.body.classList.remove('overflow-hidden');
                document.documentElement.classList.remove('overflow-hidden');
                document.body.style.overflow = '';
                document.documentElement.style.overflow = '';
            }
        };

        // Tutup modal aktif saat tombol Escape ditekan
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('[id$="Modal"], .modal-overlay');
                openModals.forEach(m => {
                    if (m.style.display === 'flex' || m.classList.contains('is-active') || (!m.classList.contains('hidden') && window.getComputedStyle(m).display !== 'none')) {
                        window.closeModal(m);
                    }
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>

