<?php

namespace App\Jobs;

use App\Models\DecreeBatch;
use App\Models\User;
use App\Services\Decree\BatchDecreeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Proses batch (pembuatan draft + alokasi nomor) di queue.
 */
class ProcessDecreeBatchJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public readonly int $batchId,
        public readonly int $actorId,
    ) {}

    public function handle(BatchDecreeService $service): void
    {
        $batch = DecreeBatch::findOrFail($this->batchId);
        $actor = User::findOrFail($this->actorId);

        $service->processBatch($batch, $actor);
    }
}
