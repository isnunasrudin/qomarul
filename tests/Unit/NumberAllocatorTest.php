<?php

use App\Models\NumberCounter;
use App\Services\Numbering\NumberAllocator;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->allocator = new NumberAllocator;
});

it('allocates sequential numbers starting from one', function () {
    expect($this->allocator->allocate('nigy:SMK:2026', 2026))->toBe(1);
    expect($this->allocator->allocate('nigy:SMK:2026', 2026))->toBe(2);
    expect($this->allocator->allocate('nigy:SMK:2026', 2026))->toBe(3);
});

it('keeps separate counters per key and per year', function () {
    expect($this->allocator->allocate('nigy:SMK:2026', 2026))->toBe(1);
    expect($this->allocator->allocate('nigy:SD1:2026', 2026))->toBe(1);
    expect($this->allocator->allocate('nigy:SMK:2027', 2027))->toBe(1);
    expect($this->allocator->allocate('nigy:SMK:2026', 2026))->toBe(2);
});

it('persists the counter row', function () {
    $this->allocator->allocate('decree:SK-PPT:SMK:2026', 2026);

    expect(NumberCounter::where('key', 'decree:SK-PPT:SMK:2026')->where('year', 2026)->value('value'))->toBe(1);
});

it('allocates 50 unique consecutive numbers under real concurrency', function () {
    // Wajib database MySQL/MariaDB bersama antar proses (mis. simqoh_test).
    // Pada suite default (sqlite :memory:) tes ini dilewati — jalankan dengan:
    //   SIMQOH_CONCURRENCY_TEST=1 ./vendor/bin/pest tests/Unit/NumberAllocatorTest.php
    if (getenv('SIMQOH_CONCURRENCY_TEST') !== '1') {
        $this->markTestSkipped('Atur SIMQOH_CONCURRENCY_TEST=1 dengan DB MySQL (simqoh_test) untuk uji konkurensi nyata.');
    }

    $processes = [];
    for ($i = 0; $i < 5; $i++) {
        $processes[] = new Process([
            PHP_BINARY,
            base_path('artisan'),
            'tinker',
            '--execute',
            '$a = app(App\Services\Numbering\NumberAllocator::class); for ($j = 0; $j < 10; $j++) { echo $a->allocate("nigy:SMK:2026", 2026)."\n"; }',
        ], null, array_merge($_ENV, [
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => 'simqoh_test',
            'DB_USERNAME' => 'simqoh',
            'DB_PASSWORD' => 'simqoh_dev_pass',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ]));
    }

    $results = [];
    foreach ($processes as $process) {
        $process->setTimeout(30)->start();
    }

    foreach ($processes as $process) {
        $process->wait();
        expect($process->getExitCode())->toBe(0);
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            if ($line !== '') {
                $results[] = (int) $line;
            }
        }
    }

    sort($results);
    expect($results)->toHaveCount(50);
    expect($results)->toBe(range(1, 50));
});
