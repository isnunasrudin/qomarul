<?php

use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Employee\EmployeeImportService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->workUnit = WorkUnit::factory()->create(['code' => 'SMK']);
    $this->position = Position::factory()->create(['code' => 'GURU-MAPEL']);
    $this->status = EmploymentStatus::factory()->create(['code' => 'TETAP']);
    $this->admin = User::factory()->foundationAdmin()->create();
});

it('reports invalid rows in preview without saving anything', function () {
    $rows = [
        ['nama' => 'Baris Valid', 'jenis_kelamin' => 'L', 'tmt_yayasan' => '2026-07-01', 'kode_satker' => 'SMK', 'kode_jabatan' => 'GURU-MAPEL', 'kode_status' => 'TETAP'],
        ['nama' => '', 'jenis_kelamin' => 'X', 'tmt_yayasan' => 'bukan-tanggal', 'kode_satker' => 'TIDAK-ADA', 'kode_jabatan' => '', 'kode_status' => ''],
    ];

    $preview = app(EmployeeImportService::class)->preview($rows);

    expect($preview['valid'])->toHaveCount(1);
    expect($preview['errors'])->toHaveCount(1);
    expect($preview['total'])->toBe(2);
    expect(Employee::count())->toBe(0);
});

it('maps L/P gender and work unit codes', function () {
    $rows = [
        ['nama' => 'Pak Guru', 'jenis_kelamin' => 'L', 'tmt_yayasan' => '2026-07-01', 'kode_satker' => 'SMK', 'kode_jabatan' => 'GURU-MAPEL', 'kode_status' => 'TETAP'],
        ['nama' => 'Bu Guru', 'jenis_kelamin' => 'P', 'tmt_yayasan' => '2026-07-01', 'kode_satker' => 'SMK', 'kode_jabatan' => 'GURU-MAPEL', 'kode_status' => 'TETAP'],
    ];

    $preview = app(EmployeeImportService::class)->preview($rows);

    expect($preview['valid'][0]['gender'])->toBe('male');
    expect($preview['valid'][1]['gender'])->toBe('female');
    expect($preview['valid'][0]['work_unit_id'])->toBe($this->workUnit->id);
});

it('imports all valid rows atomically and generates NIGY', function () {
    $rows = collect(range(1, 50))->map(fn ($i) => [
        'nama' => "GTK Uji {$i}",
        'jenis_kelamin' => $i % 2 ? 'L' : 'P',
        'tmt_yayasan' => '2026-07-01',
        'kode_satker' => 'SMK',
        'kode_jabatan' => 'GURU-MAPEL',
        'kode_status' => 'TETAP',
    ])->all();

    $preview = app(EmployeeImportService::class)->preview($rows);

    $saved = app(EmployeeImportService::class)->import($preview['valid']);

    expect($saved)->toBe(50);
    expect(Employee::count())->toBe(50);
    expect(Employee::orderBy('id')->first()->nigy)->toBe('2026SMK001');
    expect(Employee::orderByDesc('id')->first()->nigy)->toBe('2026SMK050');
});

it('leaves the database untouched when all rows are invalid', function () {
    $rows = [
        ['nama' => '', 'jenis_kelamin' => '', 'tmt_yayasan' => '', 'kode_satker' => '', 'kode_jabatan' => '', 'kode_status' => ''],
    ];

    $preview = app(EmployeeImportService::class)->preview($rows);

    expect($preview['valid'])->toHaveCount(0);
    expect(Employee::count())->toBe(0);
});

it('imports via the preview endpoint and stores in the same request flow', function () {
    $file = makeRealXlsx([
        ['nama', 'jenis_kelamin', 'tmt_yayasan', 'kode_satker', 'kode_jabatan', 'kode_status'],
        ['Endah Lestari', 'P', '2026-07-01', 'SMK', 'GURU-MAPEL', 'TETAP'],
        ['Rusak', 'X', 'nope', 'TIDAK-ADA', '', ''],
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/employees/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('daftar.xlsx', file_get_contents($file)),
        ])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Employees/ImportPreview')
            ->where('preview.total', 2)
            ->has('preview.valid', 1)
            ->has('preview.errors', 1));

    expect(Employee::count())->toBe(0);

    // konfirmasi impor dari sesi pratinjau
    $this->post('/admin/employees/import')
        ->assertRedirect(route('admin.employees.index'));

    expect(Employee::count())->toBe(1);
    expect(Employee::first()->nigy)->toBe('2026SMK001');
});

/** Buat berkas XLSX asli dengan baris yang diberikan. */
function makeRealXlsx(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
        }
    }

    $writer = new Xlsx($spreadsheet);
    $path = tempnam(sys_get_temp_dir(), 'impor').'.xlsx';
    $writer->save($path);

    return $path;
}
