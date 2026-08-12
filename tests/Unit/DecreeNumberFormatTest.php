<?php

use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\WorkUnit;
use App\Services\Decree\DecreeNumberService;
use App\Services\Numbering\NumberAllocator;

beforeEach(function () {
    $this->service = new DecreeNumberService(new NumberAllocator);
});

it('renders the default number format', function () {
    $number = $this->service->render(
        format: '{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}',
        padding: 3,
        sequence: 42,
        decreeTypeCode: 'SK-PPT',
        workUnitCode: 'SMK',
        issuedDate: '2026-07-15',
        academicYear: '2026/2027',
    );

    expect($number)->toBe('042/SK-PPT/SMK/YPP-QH/VII/2026');
});

it('pads the sequence according to the configured width', function () {
    $number = $this->service->render(
        format: '{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}',
        padding: 4,
        sequence: 7,
        decreeTypeCode: 'SK-TT',
        workUnitCode: 'SD',
        issuedDate: '2026-07-01',
        academicYear: '2026/2027',
    );

    expect($number)->toBe('0007/SK-TT/SD/YPP-QH/VII/2026');
});

it('renders roman month for december and january boundaries', function () {
    expect($this->service->render('{bulan_romawi}', 1, 1, 'X', 'X', '2026-01-05', null))->toBe('I');
    expect($this->service->render('{bulan_romawi}', 1, 1, 'X', 'X', '2026-12-25', null))->toBe('XII');
});

it('supports the numeric month and academic year tokens', function () {
    $number = $this->service->render(
        format: '{bulan}/{tahun_pelajaran}',
        padding: 3,
        sequence: 1,
        decreeTypeCode: 'X',
        workUnitCode: 'X',
        issuedDate: '2026-07-01',
        academicYear: '2026/2027',
    );

    expect($number)->toBe('07/2026/2027');
});

it('uses the issue year for the counter key separation', function () {
    // alokasi tahun 2026 dan 2027 terpisah
    $a = new DecreeNumberService(new NumberAllocator);

    $decree = Decree::factory()->create([
        'decree_type_id' => DecreeType::factory()->create(['code' => 'SK-PPT', 'number_padding' => 3])->id,
        'work_unit_id' => WorkUnit::factory()->create(['code' => 'SMK'])->id,
        'issued_date' => '2026-07-01',
        'academic_year' => '2026/2027',
    ]);

    $result2026 = $a->allocate($decree);
    expect($result2026['decree_number'])->toBe('001/SK-PPT/SMK/YPP-QH/VII/2026');
    expect($result2026['registration_number'])->toBe('E-1');

    $decree2027 = Decree::factory()->create([
        'decree_type_id' => $decree->decree_type_id,
        'work_unit_id' => $decree->work_unit_id,
        'issued_date' => '2027-01-01',
        'academic_year' => '2026/2027',
    ]);

    $result2027 = $a->allocate($decree2027);
    expect($result2027['decree_number'])->toBe('001/SK-PPT/SMK/YPP-QH/I/2027');
    expect($result2027['registration_number'])->toBe('E-2');
});
