<?php

use App\Support\IndonesianDate;

it('formats dates in Indonesian', function () {
    expect(IndonesianDate::format('2026-08-12'))->toBe('12 Agustus 2026');
    expect(IndonesianDate::format('2026-01-01'))->toBe('1 Januari 2026');
    expect(IndonesianDate::format('2024-02-29'))->toBe('29 Februari 2024');
});

it('returns empty string for null dates', function () {
    expect(IndonesianDate::format(null))->toBe('');
});
