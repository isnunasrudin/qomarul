<?php

use App\Services\Nigy\NigyGenerator;
use App\Services\Numbering\NumberAllocator;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->generator = new NigyGenerator(new NumberAllocator);
});

it('renders the default format with padding', function () {
    $nigy = $this->generator->render(
        format: '{tahun_masuk}{kode_satker}{urut}',
        padding: 3,
        workUnitCode: 'SMK',
        workUnitLevel: 'SMK',
        foundationStartDate: Carbon::parse('2026-07-15'),
        sequence: 1,
    );

    expect($nigy)->toBe('2026SMK001');
});

it('renders sequence with wider padding', function () {
    $nigy = $this->generator->render(
        format: '{tahun_masuk}{kode_satker}{urut}',
        padding: 4,
        workUnitCode: 'SD1',
        workUnitLevel: 'SD',
        foundationStartDate: Carbon::parse('2026-01-01'),
        sequence: 42,
    );

    expect($nigy)->toBe('2026SD10042');
});

it('supports all tokens including month and level', function () {
    $nigy = $this->generator->render(
        format: '{tahun_masuk}-{bulan_masuk}-{kode_jenjang}-{kode_satker}-{urut}',
        padding: 2,
        workUnitCode: 'SMP',
        workUnitLevel: 'SMP',
        foundationStartDate: Carbon::parse('2020-11-03'),
        sequence: 7,
    );

    expect($nigy)->toBe('2020-11-SMP-SMP-07');
});

it('resets the sequence per year per work unit', function () {
    expect($this->generator->nextSequence('SMK', 2026))->toBe(1);
    expect($this->generator->nextSequence('SMK', 2026))->toBe(2);
    expect($this->generator->nextSequence('SD1', 2026))->toBe(1);
    expect($this->generator->nextSequence('SMK', 2027))->toBe(1);
});

it('uses current year when start date is missing', function () {
    $nigy = $this->generator->render(
        format: '{tahun_masuk}{kode_satker}{urut}',
        padding: 3,
        workUnitCode: 'SMK',
        workUnitLevel: 'SMK',
        foundationStartDate: null,
        sequence: 5,
    );

    expect($nigy)->toBe(now()->year.'SMK005');
});
