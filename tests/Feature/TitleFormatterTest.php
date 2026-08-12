<?php

use App\Models\Employee;
use App\Support\TitleFormatter;

it('menormalisasi gelar belakang tunggal', function () {
    expect(TitleFormatter::normalizeSuffix('s.kom'))->toBe('S.Kom.');
    expect(TitleFormatter::normalizeSuffix('MPd'))->toBe('M.Pd.');
    expect(TitleFormatter::normalizeSuffix('s.pd.'))->toBe('S.Pd.');
});

it('menormalisasi dan menggabungkan banyak gelar belakang', function () {
    expect(TitleFormatter::normalizeSuffix('s.kom, Mpd'))->toBe('S.Kom., M.Pd.');
    expect(TitleFormatter::normalizeSuffix('S.Pd. , s.ag , M.Pd'))->toBe('S.Pd., S.Ag., M.Pd.');
    expect(TitleFormatter::normalizeSuffix('spdi, Mpd'))->toBe('S.Pd.I., M.Pd.');
});

it('menangani gelar belakang yang tidak dikenal secara konsisten', function () {
    expect(TitleFormatter::normalizeSuffix('mfil'))->toBe('M.Fil.');
    expect(TitleFormatter::normalizeSuffix('s.psi'))->toBe('S.Psi.');
});

it('menormalkan gelar depan', function () {
    expect(TitleFormatter::normalizePrefix('drs'))->toBe('Drs.');
    expect(TitleFormatter::normalizePrefix('dr.'))->toBe('Dr.');
    expect(TitleFormatter::normalizePrefix('prof dr'))->toBe('Prof. Dr.');
    expect(TitleFormatter::normalizePrefix('Hj'))->toBe('Hj.');
});

it('mengembalikan null untuk nilai kosong', function () {
    expect(TitleFormatter::normalizePrefix(null))->toBeNull();
    expect(TitleFormatter::normalizeSuffix(''))->toBeNull();
});

it('mutator menyimpan gelar dalam bentuk normal', function () {
    $employee = Employee::factory()->create([
        'title_prefix' => 'drs',
        'name' => 'Ahmad Fauzi',
        'title_suffix' => 's.kom, Mpd',
    ]);

    expect($employee->fresh()->title_prefix)->toBe('Drs.');
    expect($employee->fresh()->title_suffix)->toBe('S.Kom., M.Pd.');
    expect($employee->fresh()->full_name)->toBe('Drs. Ahmad Fauzi, S.Kom., M.Pd.');
});
