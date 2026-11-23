@extends('layouts.admin')

@section('page_title', 'Rekap Absensi Bulanan')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">SIAKAD · Laporan kehadiran</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Rekap absensi bulanan</h2>
            <p class="mt-1 text-sm text-slate-500">Ringkasan kehadiran setiap siswa berdasarkan hari yang tercatat.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.attendance.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left"></i> Absensi harian
            </a>
            @if($selectedClass && $students->isNotEmpty())
                <a href="{{ route('admin.attendance.monthly.export_pdf', ['kelas' => $selectedClass, 'periode' => $selectedPeriod]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" title="Unduh PDF">
                    <i class="fa-solid fa-file-pdf text-rose-600"></i> PDF
                </a>
                <button type="button" id="download-monthly-attendance-png" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" title="Unduh gambar PNG">
                    <i class="fa-solid fa-image text-emerald-700"></i> Gambar
                </button>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.attendance.monthly') }}" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_220px_auto] sm:items-end">
        <label class="text-xs font-semibold text-slate-700">Kelas
            <select name="kelas" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100" @disabled($classes->isEmpty())>
                @forelse($classes as $className)
                    <option value="{{ $className }}" @selected($selectedClass === $className)>{{ $className }}</option>
                @empty
                    <option value="">Belum ada kelas</option>
                @endforelse
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-700">Periode
            <input type="month" name="periode" value="{{ $selectedPeriod }}" max="{{ today()->format('Y-m') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
        </label>
        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-700" @disabled($classes->isEmpty())>
            <i class="fa-solid fa-filter"></i> Tampilkan
        </button>
    </form>

    @if($selectedClass)
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Hari absensi</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['hari_absensi'] }}</div>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Hadir</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['hadir'] }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Sakit</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['sakit'] }}</div>
            </div>
            <div class="rounded-lg border border-sky-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-sky-700">Izin</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['izin'] }}</div>
            </div>
            <div class="rounded-lg border border-rose-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-rose-700">Alpa</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['alpa'] }}</div>
            </div>
            <div class="rounded-lg border border-teal-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-teal-700">Tingkat hadir</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['persentase_hadir'], 1) }}%</div>
            </div>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-1 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">{{ $selectedClass }} · {{ $periodLabel }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $students->count() }} siswa · persentase tiap siswa dihitung dari hari absensi yang tersimpan</p>
                </div>
                <span class="text-xs font-semibold text-slate-500">{{ $rows->count() }} baris</span>
            </div>

            @if($rows->isNotEmpty())
                <div class="hidden bg-slate-50 px-4 py-3 text-[10px] font-bold uppercase tracking-wide text-slate-500 xl:grid xl:grid-cols-[3rem_minmax(0,1fr)_repeat(5,5rem)_7rem]">
                    <span class="text-center">No</span>
                    <span>Siswa</span>
                    <span class="text-center">Hadir</span>
                    <span class="text-center">Sakit</span>
                    <span class="text-center">Izin</span>
                    <span class="text-center">Alpa</span>
                    <span class="text-center">Hari</span>
                    <span class="text-right">Kehadiran</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($rows as $row)
                        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 xl:grid-cols-[3rem_minmax(0,1fr)_repeat(5,5rem)_7rem] xl:items-center xl:gap-0 xl:px-4 xl:py-3">
                            <div class="col-span-2 flex min-w-0 items-center gap-3 sm:col-span-3 xl:col-span-1 xl:gap-0">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 xl:hidden">{{ $row['no'] }}</span>
                                <span class="hidden text-center text-slate-400 xl:block">{{ $row['no'] }}</span>
                                <div class="min-w-0 xl:pl-0">
                                    <div class="break-words text-sm font-bold text-slate-800">{{ $row['nama'] }}</div>
                                    <div class="mt-0.5 break-all text-[11px] text-slate-500">{{ $row['nis'] }}</div>
                                </div>
                            </div>
                            <div class="min-w-0 rounded-md bg-slate-50 p-2 xl:bg-transparent xl:p-0">
                                <span class="mb-1 block text-[10px] font-semibold uppercase text-slate-500 xl:hidden">Hadir</span>
                                <span class="font-semibold text-emerald-700">{{ $row['hadir'] }}</span>
                            </div>
                            <div class="min-w-0 rounded-md bg-slate-50 p-2 xl:bg-transparent xl:p-0">
                                <span class="mb-1 block text-[10px] font-semibold uppercase text-slate-500 xl:hidden">Sakit</span>
                                <span class="font-semibold text-amber-700">{{ $row['sakit'] }}</span>
                            </div>
                            <div class="min-w-0 rounded-md bg-slate-50 p-2 xl:bg-transparent xl:p-0">
                                <span class="mb-1 block text-[10px] font-semibold uppercase text-slate-500 xl:hidden">Izin</span>
                                <span class="font-semibold text-sky-700">{{ $row['izin'] }}</span>
                            </div>
                            <div class="min-w-0 rounded-md bg-slate-50 p-2 xl:bg-transparent xl:p-0">
                                <span class="mb-1 block text-[10px] font-semibold uppercase text-slate-500 xl:hidden">Alpa</span>
                                <span class="font-semibold text-rose-700">{{ $row['alpa'] }}</span>
                            </div>
                            <div class="min-w-0 rounded-md bg-slate-50 p-2 xl:bg-transparent xl:p-0">
                                <span class="mb-1 block text-[10px] font-semibold uppercase text-slate-500 xl:hidden">Hari tercatat</span>
                                <span class="text-slate-600">{{ $row['hari_tercatat'] }}</span>
                            </div>
                            <div class="min-w-0 rounded-md bg-slate-50 p-2 xl:bg-transparent xl:p-0 xl:text-right">
                                <span class="mb-1 block text-[10px] font-semibold uppercase text-slate-500 xl:hidden">Kehadiran</span>
                                @if($row['persentase'] === null)
                                    <span class="text-[11px] text-slate-400">Belum ada data</span>
                                @else
                                    <span class="font-bold text-slate-800">{{ number_format($row['persentase'], 1) }}%</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-12 text-center">
                    <i class="fa-solid fa-chart-column text-2xl text-slate-300"></i>
                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada siswa untuk direkap</p>
                    <p class="mt-1 text-xs text-slate-500">Tambahkan siswa ke kelas ini atau pilih periode lain.</p>
                </div>
            @endif
        </section>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center">
            <i class="fa-solid fa-chart-column text-3xl text-slate-300"></i>
            <p class="mt-3 text-sm font-bold text-slate-700">Data kelas belum tersedia</p>
            <p class="mt-1 text-xs text-slate-500">Tambahkan siswa dan kelas untuk membuat rekap bulanan.</p>
        </div>
    @endif
</div>

@if($selectedClass && $rows->isNotEmpty())
<script>
    const monthlyRows = @js($rows);
    const monthlySummary = @js($summary);
    const monthlyClass = @js($selectedClass);
    const monthlyPeriod = @js($periodLabel);

    document.getElementById('download-monthly-attendance-png')?.addEventListener('click', () => {
        const canvas = document.createElement('canvas');
        const width = 1600;
        const rowHeight = 48;
        const topHeight = 250;
        canvas.width = width;
        canvas.height = topHeight + monthlyRows.length * rowHeight + 70;
        const context = canvas.getContext('2d');

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = '#047857';
        context.fillRect(0, 0, width, 16);
        context.fillStyle = '#0f172a';
        context.font = '700 32px Arial';
        context.fillText('SMP NEGERI 3 NGAWEN', 64, 76);
        context.font = '700 23px Arial';
        context.fillText('REKAP ABSENSI BULANAN', 64, 118);
        context.fillStyle = '#475569';
        context.font = '16px Arial';
        context.fillText(`Kelas ${monthlyClass}  ·  ${monthlyPeriod}`, 64, 152);

        const summaries = [
            ['Hari absensi', monthlySummary.hari_absensi],
            ['Hadir', monthlySummary.hadir],
            ['Sakit', monthlySummary.sakit],
            ['Izin', monthlySummary.izin],
            ['Alpa', monthlySummary.alpa],
            ['Tingkat hadir', `${Number(monthlySummary.persentase_hadir).toFixed(1)}%`],
        ];
        summaries.forEach(([label, count], index) => {
            const x = 64 + index * 245;
            context.fillStyle = '#f1f5f9';
            context.fillRect(x, 178, 220, 48);
            context.fillStyle = '#334155';
            context.font = '14px Arial';
            context.fillText(label, x + 12, 198);
            context.font = '700 18px Arial';
            context.fillText(String(count), x + 12, 218);
        });

        const columns = [
            { label: 'NO', x: 64, width: 60, key: 'no' },
            { label: 'NIS', x: 124, width: 180, key: 'nis' },
            { label: 'NAMA SISWA', x: 304, width: 460, key: 'nama' },
            { label: 'HADIR', x: 764, width: 120, key: 'hadir' },
            { label: 'SAKIT', x: 884, width: 120, key: 'sakit' },
            { label: 'IZIN', x: 1004, width: 120, key: 'izin' },
            { label: 'ALPA', x: 1124, width: 120, key: 'alpa' },
            { label: 'HARI', x: 1244, width: 140, key: 'hari_tercatat' },
            { label: 'KEHADIRAN', x: 1384, width: 150, key: 'persentase' },
        ];
        let y = topHeight;
        context.fillStyle = '#0f172a';
        context.fillRect(64, y, 1470, 42);
        context.fillStyle = '#ffffff';
        context.font = '700 13px Arial';
        columns.forEach((column) => context.fillText(column.label, column.x + 10, y + 26));

        monthlyRows.forEach((row, index) => {
            y = topHeight + 42 + index * rowHeight;
            context.fillStyle = index % 2 === 0 ? '#ffffff' : '#f8fafc';
            context.fillRect(64, y, 1470, rowHeight);
            context.fillStyle = '#334155';
            context.font = '14px Arial';
            columns.forEach((column) => {
                const value = column.key === 'persentase' && row[column.key] !== null
                    ? `${Number(row[column.key]).toFixed(1)}%`
                    : row[column.key] ?? '-';
                context.fillText(String(value), column.x + 10, y + 29, column.width - 20);
            });
            context.strokeStyle = '#e2e8f0';
            context.beginPath();
            context.moveTo(64, y + rowHeight);
            context.lineTo(1534, y + rowHeight);
            context.stroke();
        });

        canvas.toBlob((blob) => {
            if (!blob) return;
            const link = document.createElement('a');
            link.download = `Rekap-Absensi-${monthlyClass}-${monthlyPeriod.replace(' ', '-')}.png`;
            const objectUrl = URL.createObjectURL(blob);
            link.href = objectUrl;
            link.click();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
        }, 'image/png');
    });
</script>
@endif
@endsection