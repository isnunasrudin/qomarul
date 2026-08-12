<?php

namespace App\Jobs;

use App\Enums\DecreeBatchStatus;
use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\User;
use App\Services\Decree\DecreeIssueService;
use App\Services\Decree\DecreeWorkflowService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tanda tangani satu SK (dipanggil per item saat batch ditandatangani).
 * Kegagalan satu item tidak menggagalkan batch (F5.24).
 *
 * Catatan: progres batch diperbarui DI LUAR transaksi penerbitan agar
 * kegagalan pembaruan tidak membatalkan SK yang sudah terbit.
 */
class SignDecreeJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public readonly int $decreeId,
        public readonly int $actorId,
    ) {}

    /**
     * @return array{ok: bool, error: ?string}
     */
    public function handle(DecreeWorkflowService $workflow, DecreeIssueService $issueService): array
    {
        $result = DB::transaction(function () use ($workflow, $issueService): array {
            $decree = Decree::find($this->decreeId);
            $actor = User::find($this->actorId);

            if (! $decree || ! $actor) {
                return ['ok' => false, 'error' => 'Data SK atau penanda tangan tidak ditemukan.'];
            }

            try {
                $workflow->transition($decree, DecreeStatus::Issued, $actor);
                $issueService->issue($decree->fresh(), $actor);

                return ['ok' => true, 'error' => null];
            } catch (RuntimeException $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        });

        if ($result['ok']) {
            $this->updateBatchProgress();
        }

        return $result;
    }

    /**
     * Perbarui progres batch setelah satu SK berhasil diterbitkan;
     * tandai completed saat seluruh SK yang diharapkan telah terbit.
     * Tidak boleh melempar — progres hanyalah pelengkap.
     */
    protected function updateBatchProgress(): void
    {
        try {
            $decree = Decree::find($this->decreeId);
            $batch = $decree?->batch;

            if (! $batch) {
                return;
            }

            $expected = max(0, $batch->total - $batch->failed);
            $issued = $batch->decrees()->where('status', DecreeStatus::Issued->value)->count();

            $batch->update([
                'succeeded' => $issued,
                'status' => $issued >= $expected
                    ? DecreeBatchStatus::Completed->value
                    : DecreeBatchStatus::Signing->value,
            ]);
        } catch (\Throwable) {
            // abaikan — jangan biarkan progres menggagalkan SK yang sudah terbit
        }
    }
}
