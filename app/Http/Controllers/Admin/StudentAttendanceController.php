<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAttendanceController extends Controller
{
    private const STATUS_OPTIONS = [
        'hadir' => 'Hadir',
        'sakit' => 'Sakit',
        'izin' => 'Izin',
        'alpa' => 'Alpa',
    ];

    public function index(Request $request)
    {
        $classes = $this->classOptions();
        $filters = $request->validate([
            'kelas' => ['nullable', 'string', Rule::in($classes->all())],
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $selectedClass = $filters['kelas'] ?? $classes->first();
        $selectedDate = $filters['tanggal'] ?? today()->toDateString();
        $students = $selectedClass
            ? $this->studentsForDate($selectedClass, $selectedDate)
            : collect();
        $attendanceByStudent = $selectedClass
            ? StudentAttendance::where('kelas', $selectedClass)->whereDate('tanggal', $selectedDate)->get()->keyBy('user_id')
            : collect();
        $summary = array_fill_keys(['hadir', 'sakit', 'izin', 'alpa', 'belum_diisi'], 0);

        foreach ($students as $student) {
            $status = $attendanceByStudent->get($student->id)?->status;
            $summary[$status && isset($summary[$status]) ? $status : 'belum_diisi']++;
        }

        $exportRows = $students->values()->map(function (User $student, int $index) use ($attendanceByStudent): array {
            $attendance = $attendanceByStudent->get($student->id);

            return [
                'no' => $index + 1,
                'nis' => $student->nis ?: $student->username ?: '-',
                'nama' => $student->nama_lengkap ?: $student->name ?: '-',
                'status' => self::STATUS_OPTIONS[$attendance?->status] ?? 'Belum diisi',
                'catatan' => $attendance?->catatan ?: '-',
            ];
        });

        return view('admin.attendance.index', [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'selectedDate' => $selectedDate,
            'students' => $students,
            'attendanceByStudent' => $attendanceByStudent,
            'statusOptions' => self::STATUS_OPTIONS,
            'summary' => $summary,
            'exportRows' => $exportRows,
        ]);
    }

    public function monthly(Request $request)
    {
        $classes = $this->classOptions();
        $filters = $request->validate([
            'kelas' => ['nullable', 'string', Rule::in($classes->all())],
            'periode' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedClass = $filters['kelas'] ?? $classes->first();
        $selectedPeriod = $filters['periode'] ?? today()->format('Y-m');
        $this->ensurePeriodIsAvailable($selectedPeriod);

        $report = $selectedClass
            ? $this->monthlyReportData($selectedClass, $selectedPeriod)
            : $this->emptyMonthlyReport();

        return view('admin.attendance.monthly', [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'selectedPeriod' => $selectedPeriod,
            ...$report,
        ]);
    }

    public function exportMonthlyPdf(Request $request)
    {
        $classes = $this->classOptions();
        $filters = $request->validate([
            'kelas' => ['required', 'string', Rule::in($classes->all())],
            'periode' => ['required', 'date_format:Y-m'],
        ]);
        $this->ensurePeriodIsAvailable($filters['periode']);
        $report = $this->monthlyReportData($filters['kelas'], $filters['periode']);

        return PdfService::exportMonthlyAttendancePdf(
            $filters['kelas'],
            $report['periodLabel'],
            $report['rows'],
            $report['summary'],
        );
    }

    public function store(Request $request)
    {
        $classes = $this->classOptions();
        $validated = $request->validate([
            'kelas' => ['required', 'string', Rule::in($classes->all())],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'students' => ['required', 'array', 'min:1'],
            'students.*' => ['required', 'array:status,catatan'],
            'students.*.status' => ['required', Rule::in(array_keys(self::STATUS_OPTIONS))],
            'students.*.catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $students = $this->studentsForDate($validated['kelas'], $validated['tanggal']);
        $expectedIds = $students->modelKeys();
        $submittedIds = collect(array_keys($validated['students']))
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        sort($expectedIds);

        if ($submittedIds !== $expectedIds) {
            throw ValidationException::withMessages([
                'students' => 'Daftar siswa berubah. Muat ulang halaman sebelum menyimpan absensi.',
            ]);
        }

        $timestamp = now();
        $records = collect($validated['students'])->map(function (array $attendance, string|int $studentId) use ($validated, $request, $timestamp): array {
            return [
                'user_id' => (int) $studentId,
                'recorded_by' => $request->user()->id,
                'tanggal' => $validated['tanggal'],
                'kelas' => $validated['kelas'],
                'status' => $attendance['status'],
                'catatan' => $attendance['catatan'] ?? null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();

        DB::transaction(function () use ($records): void {
            StudentAttendance::upsert($records, ['user_id', 'tanggal'], [
                'recorded_by', 'kelas', 'status', 'catatan', 'updated_at',
            ]);
        });

        return redirect()->route('admin.attendance.index', [
            'kelas' => $validated['kelas'],
            'tanggal' => $validated['tanggal'],
        ])->with('success', 'Absensi siswa berhasil disimpan.');
    }

    public function exportPdf(Request $request)
    {
        $classes = $this->classOptions();
        $filters = $request->validate([
            'kelas' => ['required', 'string', Rule::in($classes->all())],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $students = $this->studentsForDate($filters['kelas'], $filters['tanggal']);
        $attendanceByStudent = StudentAttendance::where('kelas', $filters['kelas'])
            ->whereDate('tanggal', $filters['tanggal'])
            ->get()
            ->keyBy('user_id');
        $summary = array_fill_keys(['hadir', 'sakit', 'izin', 'alpa', 'belum_diisi'], 0);
        $rows = $students->values()->map(function (User $student, int $index) use ($attendanceByStudent, &$summary): array {
            $attendance = $attendanceByStudent->get($student->id);
            $status = $attendance?->status;
            $summary[$status && isset($summary[$status]) ? $status : 'belum_diisi']++;

            return [
                'no' => $index + 1,
                'nis' => $student->nis ?: $student->username ?: '-',
                'nama' => $student->nama_lengkap ?: $student->name ?: '-',
                'status' => self::STATUS_OPTIONS[$status] ?? 'Belum diisi',
                'catatan' => $attendance?->catatan ?: '-',
            ];
        });

        return PdfService::exportAttendancePdf(
            $filters['kelas'],
            date('d/m/Y', strtotime($filters['tanggal'])),
            $rows,
            $summary,
        );
    }

    private function classOptions(): Collection
    {
        return $this->activeStudents()
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->merge(StudentAttendance::query()->distinct()->pluck('kelas'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function studentsForDate(string $className, string $date): Collection
    {
        $currentStudents = $this->activeStudents()->where('kelas', $className)->get();
        $previouslyRecordedStudents = StudentAttendance::with('student')
            ->where('kelas', $className)
            ->whereDate('tanggal', $date)
            ->get()
            ->pluck('student')
            ->filter();

        return $currentStudents
            ->concat($previouslyRecordedStudents)
            ->unique('id')
            ->sortBy('nama_lengkap')
            ->values();
    }

    private function monthlyReportData(string $className, string $period): array
    {
        $month = Carbon::createFromFormat('Y-m-d', $period.'-01');
        $startDate = $month->copy()->startOfMonth()->toDateString();
        $endDate = $month->copy()->endOfMonth()->toDateString();
        $students = $this->studentsForPeriod($className, $startDate, $endDate);
        $aggregates = StudentAttendance::query()
            ->where('kelas', $className)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select('user_id')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS hadir', ['hadir'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS sakit', ['sakit'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS izin', ['izin'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS alpa', ['alpa'])
            ->selectRaw('COUNT(*) AS hari_tercatat')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
        $rows = $students->values()->map(function (User $student, int $index) use ($aggregates): array {
            $aggregate = $aggregates->get($student->id);
            $daysRecorded = (int) ($aggregate?->hari_tercatat ?? 0);
            $presentDays = (int) ($aggregate?->hadir ?? 0);

            return [
                'no' => $index + 1,
                'nis' => $student->nis ?: $student->username ?: '-',
                'nama' => $student->nama_lengkap ?: $student->name ?: '-',
                'hadir' => $presentDays,
                'sakit' => (int) ($aggregate?->sakit ?? 0),
                'izin' => (int) ($aggregate?->izin ?? 0),
                'alpa' => (int) ($aggregate?->alpa ?? 0),
                'hari_tercatat' => $daysRecorded,
                'persentase' => $daysRecorded > 0 ? round($presentDays / $daysRecorded * 100, 1) : null,
            ];
        });
        $totalRecordedDays = (int) $rows->sum('hari_tercatat');
        $summary = [
            'hari_absensi' => StudentAttendance::query()
                ->where('kelas', $className)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->distinct()
                ->count('tanggal'),
            'hadir' => (int) $rows->sum('hadir'),
            'sakit' => (int) $rows->sum('sakit'),
            'izin' => (int) $rows->sum('izin'),
            'alpa' => (int) $rows->sum('alpa'),
            'persentase_hadir' => $totalRecordedDays > 0
                ? round($rows->sum('hadir') / $totalRecordedDays * 100, 1)
                : 0,
        ];

        return [
            'students' => $students,
            'rows' => $rows,
            'summary' => $summary,
            'periodLabel' => $month->locale('id')->translatedFormat('F Y'),
        ];
    }

    private function studentsForPeriod(string $className, string $startDate, string $endDate): Collection
    {
        $recordedStudentIds = StudentAttendance::query()
            ->where('kelas', $className)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->distinct()
            ->pluck('user_id');
        $currentStudents = $this->activeStudents()->where('kelas', $className)->get();
        $previouslyRecordedStudents = User::whereIn('id', $recordedStudentIds)->get();

        return $currentStudents
            ->concat($previouslyRecordedStudents)
            ->unique('id')
            ->sortBy('nama_lengkap')
            ->values();
    }

    private function emptyMonthlyReport(): array
    {
        return [
            'students' => collect(),
            'rows' => collect(),
            'summary' => [
                'hari_absensi' => 0,
                'hadir' => 0,
                'sakit' => 0,
                'izin' => 0,
                'alpa' => 0,
                'persentase_hadir' => 0,
            ],
            'periodLabel' => today()->locale('id')->translatedFormat('F Y'),
        ];
    }

    private function ensurePeriodIsAvailable(string $period): void
    {
        if ($period > today()->format('Y-m')) {
            throw ValidationException::withMessages([
                'periode' => 'Rekap untuk bulan yang akan datang belum tersedia.',
            ]);
        }
    }

    private function activeStudents(): Builder
    {
        $students = User::query()->where('role_id', 3);

        if (Schema::hasColumn('users', 'status_siswa')) {
            $students->where(function (Builder $query): void {
                $query->where('status_siswa', 'aktif')
                    ->orWhereNull('status_siswa')
                    ->orWhere('status_siswa', '');
            });
        }

        return $students;
    }
}
