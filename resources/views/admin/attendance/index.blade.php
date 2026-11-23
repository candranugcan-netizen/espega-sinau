@extends('layouts.admin')

@section('page_title', 'Absensi Siswa')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">SIAKAD · Kehadiran</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Absensi siswa</h2>
            <p class="mt-1 text-sm text-slate-500">Pencatatan kehadiran harian dan rekap kelas.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.attendance.monthly') }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-3.5 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50">
                <i class="fa-solid fa-chart-pie"></i> Rekap bulanan
            </a>
            @if($selectedClass && $students->isNotEmpty())
                <a href="{{ route('admin.attendance.export_pdf', ['kelas' => $selectedClass, 'tanggal' => $selectedDate]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" title="Unduh PDF">
                    <i class="fa-solid fa-file-pdf text-rose-600"></i> PDF
                </a>
                <button type="button" id="download-attendance-png" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" title="Unduh gambar PNG">
                    <i class="fa-solid fa-image text-emerald-700"></i> Gambar
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.attendance.index') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_220px_auto] sm:items-end">
        <label class="text-xs font-semibold text-slate-700">Kelas
            <select name="kelas" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100" @disabled($classes->isEmpty())>
                @forelse($classes as $className)
                    <option value="{{ $className }}" @selected($selectedClass === $className)>{{ $className }}</option>
                @empty
                    <option value="">Belum ada kelas</option>
                @endforelse
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-700">Tanggal absensi
            <input type="date" name="tanggal" value="{{ $selectedDate }}" max="{{ today()->toDateString() }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
        </label>
        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-700" @disabled($classes->isEmpty())>
            <i class="fa-solid fa-filter"></i> Tampilkan
        </button>
    </form>

    @if($selectedClass)
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach(['hadir' => ['Hadir', 'border-emerald-200', 'text-emerald-700'], 'sakit' => ['Sakit', 'border-amber-200', 'text-amber-700'], 'izin' => ['Izin', 'border-sky-200', 'text-sky-700'], 'alpa' => ['Alpa', 'border-rose-200', 'text-rose-700'], 'belum_diisi' => ['Belum diisi', 'border-slate-200', 'text-slate-500']] as $key => [$label, $borderClass, $labelClass])
                <div class="rounded-lg border {{ $borderClass }} bg-white px-4 py-3">
                    <div class="text-[11px] font-bold uppercase tracking-wide {{ $labelClass }}">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary[$key] }}</div>
                </div>
            @endforeach
        </div>

        <form action="{{ route('admin.attendance.store') }}" method="POST" id="attendance-form" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            @csrf
            <input type="hidden" name="kelas" value="{{ $selectedClass }}">
            <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Daftar hadir · {{ $selectedClass }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }} · {{ $students->count() }} siswa</p>
                </div>
                @if($students->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="mark-all-present" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50">
                            <i class="fa-solid fa-check-double"></i> Tandai semua hadir
                        </button>
                        <button class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan absensi
                        </button>
                    </div>
                @endif
            </div>

            @if($students->isNotEmpty())
                <div class="hidden bg-slate-50 px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500 xl:grid xl:grid-cols-[3rem_minmax(0,1fr)_12rem_14rem]">
                    <span class="text-center">No</span>
                    <span>Siswa</span>
                    <span>Status</span>
                    <span>Catatan</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($students as $index => $student)
                        @php($attendance = $attendanceByStudent->get($student->id))
                        <div class="grid gap-3 p-4 xl:grid-cols-[3rem_minmax(0,1fr)_12rem_14rem] xl:items-center xl:px-4 xl:py-3">
                            <span class="hidden text-center text-slate-400 xl:block">{{ $index + 1 }}</span>
                            <div class="flex min-w-0 items-center gap-3 xl:gap-0">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 xl:hidden">{{ $index + 1 }}</span>
                                <div class="min-w-0">
                                    <div class="break-words text-sm font-bold text-slate-800">{{ $student->nama_lengkap ?: $student->name ?: 'Siswa' }}</div>
                                    <div class="mt-0.5 break-all text-[11px] text-slate-500">{{ $student->nis ?: $student->username ?: 'Tanpa NIS' }}</div>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <label for="attendance-status-{{ $student->id }}" class="mb-1 block text-[11px] font-semibold text-slate-600 xl:hidden">Status kehadiran</label>
                                <select id="attendance-status-{{ $student->id }}" name="students[{{ $student->id }}][status]" required data-attendance-status class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                    <option value="">Pilih status</option>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('students.'.$student->id.'.status', $attendance?->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0">
                                <label for="attendance-note-{{ $student->id }}" class="mb-1 block text-[11px] font-semibold text-slate-600 xl:hidden">Catatan (opsional)</label>
                                <input id="attendance-note-{{ $student->id }}" name="students[{{ $student->id }}][catatan]" value="{{ old('students.'.$student->id.'.catatan', $attendance?->catatan) }}" maxlength="255" placeholder="Opsional" class="min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-[11px] text-slate-500 sm:px-5">
                    Status kosong wajib dilengkapi sebelum disimpan. Data yang sudah ada dapat diperbarui.
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <p class="text-xs text-slate-500">Sudah selesai mengisi semua siswa?</p>
                    <button type="submit" id="save-attendance-bottom" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 sm:w-auto">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan absensi
                    </button>
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <i class="fa-solid fa-user-group text-2xl text-slate-300"></i>
                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada siswa di kelas ini</p>
                    <p class="mt-1 text-xs text-slate-500">Pilih kelas lain atau tambahkan data siswa terlebih dahulu.</p>
                </div>
            @endif
        </form>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center">
            <i class="fa-solid fa-clipboard-user text-3xl text-slate-300"></i>
            <p class="mt-3 text-sm font-bold text-slate-700">Data kelas belum tersedia</p>
            <p class="mt-1 text-xs text-slate-500">Tambahkan siswa dan kelas untuk mulai mencatat absensi.</p>
        </div>
    @endif
</div>

@if($selectedClass && $students->isNotEmpty())
<script>
    const attendanceRows = @js($exportRows);
    const attendanceSummary = @js($summary);
    const attendanceClass = @js($selectedClass);
    const attendanceDate = @js($selectedDate);

    document.getElementById('mark-all-present')?.addEventListener('click', () => {
        document.querySelectorAll('[data-attendance-status]').forEach((select) => {
            select.value = 'hadir';
        });
    });

    document.getElementById('download-attendance-png')?.addEventListener('click', () => {
        const canvas = document.createElement('canvas');
        const width = 1500;
        const rowHeight = 48;
        const topHeight = 250;
        canvas.width = width;
        canvas.height = topHeight + attendanceRows.length * rowHeight + 70;
        const context = canvas.getContext('2d');

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = '#047857';
        context.fillRect(0, 0, width, 16);
        context.fillStyle = '#0f172a';
        context.font = '700 32px Arial';
        context.fillText('SMP NEGERI 3 NGAWEN', 64, 76);
        context.font = '700 23px Arial';
        context.fillText('DAFTAR HADIR SISWA', 64, 118);
        context.font = '16px Arial';
        context.fillStyle = '#475569';
        context.fillText(`Kelas ${attendanceClass}  ·  ${attendanceDate}`, 64, 152);

        const summaryLabels = [
            ['Hadir', attendanceSummary.hadir],
            ['Sakit', attendanceSummary.sakit],
            ['Izin', attendanceSummary.izin],
            ['Alpa', attendanceSummary.alpa],
            ['Belum diisi', attendanceSummary.belum_diisi],
        ];
        summaryLabels.forEach(([label, count], index) => {
            const x = 64 + index * 270;
            context.fillStyle = '#f1f5f9';
            context.fillRect(x, 178, 240, 48);
            context.fillStyle = '#334155';
            context.font = '14px Arial';
            context.fillText(label, x + 14, 198);
            context.font = '700 18px Arial';
            context.fillText(String(count), x + 14, 218);
        });

        const columns = [
            { label: 'NO', x: 64, width: 70, key: 'no' },
            { label: 'NIS', x: 134, width: 210, key: 'nis' },
            { label: 'NAMA SISWA', x: 344, width: 590, key: 'nama' },
            { label: 'STATUS', x: 934, width: 210, key: 'status' },
            { label: 'CATATAN', x: 1144, width: 290, key: 'catatan' },
        ];
        let y = topHeight;
        context.fillStyle = '#0f172a';
        context.fillRect(64, y, 1370, 42);
        context.fillStyle = '#ffffff';
        context.font = '700 13px Arial';
        columns.forEach((column) => context.fillText(column.label, column.x + 12, y + 26));

        attendanceRows.forEach((row, index) => {
            y = topHeight + 42 + index * rowHeight;
            context.fillStyle = index % 2 === 0 ? '#ffffff' : '#f8fafc';
            context.fillRect(64, y, 1370, rowHeight);
            context.fillStyle = '#334155';
            context.font = '14px Arial';
            columns.forEach((column) => {
                context.fillText(String(row[column.key] ?? '-'), column.x + 12, y + 29, column.width - 24);
            });
            context.strokeStyle = '#e2e8f0';
            context.beginPath();
            context.moveTo(64, y + rowHeight);
            context.lineTo(1434, y + rowHeight);
            context.stroke();
        });

        canvas.toBlob((blob) => {
            if (!blob) return;
            const link = document.createElement('a');
            link.download = `Daftar-Hadir-${attendanceClass}-${attendanceDate}.png`;
            const objectUrl = URL.createObjectURL(blob);
            link.href = objectUrl;
            link.click();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
        }, 'image/png');
    });
</script>
@endif
@endsection