<?php

use App\Support\ServicePeriod;
use Illuminate\Support\Carbon;

it('computes full years and remaining months', function () {
    $period = ServicePeriod::between('2020-07-01', '2026-08-12');

    expect($period->years)->toBe(6);
    expect($period->months)->toBe(1);
    expect($period->label())->toBe('6 tahun 1 bulan');
});

it('treats same month as zero months', function () {
    $period = ServicePeriod::between('2020-07-15', '2026-07-20');

    expect($period->years)->toBe(6);
    expect($period->months)->toBe(0);
});

it('counts a day before anniversary as previous month', function () {
    $period = ServicePeriod::between('2020-08-01', '2026-07-31');

    expect($period->years)->toBe(5);
    expect($period->months)->toBe(11);
});

it('handles leap year boundaries', function () {
    $period = ServicePeriod::between('2020-02-29', '2024-02-29');

    expect($period->years)->toBe(4);
    expect($period->months)->toBe(0);
});

it('returns zero for future start dates', function () {
    $period = ServicePeriod::between(Carbon::now()->addMonth(), now());

    expect($period->years)->toBe(0);
    expect($period->months)->toBe(0);
});

it('returns zero when start date is missing', function () {
    $period = ServicePeriod::between(null);

    expect($period->years)->toBe(0);
    expect($period->months)->toBe(0);
});
