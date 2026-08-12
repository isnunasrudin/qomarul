<?php

namespace App\Services\Decree;

use App\Enums\DecreeBatchStatus;
use App\Enums\DecreeStatus;
use App\Enums\UserRole;
use App\Models\Decree;
use App\Models\DecreeBatch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alur batch SK (PRD §5.5.3, F5.18–F5.26).
 */
class BatchDecreeService
{
    public const MAX_BATCH_SIZE = 500;

    public function __construct(
        private readonly DecreeWorkflowService $workflow,
        private readonly DecreeIssueService $issueService,
    ) {}

    /**
     * Buat batch + draft SK untuk seluruh penerima terpilih.
     *
     * @param  array<int>  $employeeIds
     * @param  array<string, mixed>  $params  name, decree_type_id, academic_year, effective_date, issued_date, issued_place, appointed_as
     */
    public function createBatch(array $employeeIds, array $params, User $actor): DecreeBatch
    {
        $employeeIds = array_values(array_unique($employeeIds));

        if (count($employeeIds) > self::MAX_BATCH_SIZE) {
            throw new RuntimeException('Batas aman satu batch adalah '.self::MAX_BATCH_SIZE.' SK.');
        }

        if ($employeeIds === []) {
            throw new RuntimeException('Pilih minimal satu GTK penerima.');
        }

        return DB::transaction(function () use ($employeeIds, $params, $actor): DecreeBatch {
            $batch = DecreeBatch::create([
                'name' => $params['name'],
                'decree_type_id' => $params['decree_type_id'],
                'academic_year' => $params['academic_year'],
                'effective_date' => $params['effective_date'],
                'issued_date' => $params['issued_date'],
                'total' => count($employeeIds),
                'status' => DecreeBatchStatus::Preparing,
                'created_by' => $actor->id,
            ]);

            $employees = Employee::query()
                ->whereIn('id', $employeeIds)
                ->with('workUnit:id,code,name')
                ->get();

            foreach ($employees as $employee) {
                Decree::create([
                    'uuid' => (string) Str::uuid(),
                    'decree_type_id' => $params['decree_type_id'],
                    'employee_id' => $employee->id,
                    'work_unit_id' => $employee->work_unit_id,
                    'decree_batch_id' => $batch->id,
                    'academic_year' => $params['academic_year'],
                    'effective_date' => $params['effective_date'],
                    'issued_date' => $params['issued_date'],
                    'issued_place' => $params['issued_place'] ?? null,
                    'appointed_as' => $params['appointed_as'] ?? $employee->position?->name,
                    'position_snapshot' => $params['position_snapshot'] ?? $employee->position?->name,
                    'status' => DecreeStatus::Draft,
                    'created_by' => $actor->id,
                ]);
            }

            return $batch;
        });
    }

    /**
     * Proses batch: validasi kelengkapan tiap GTK → alokasi nomor (verified).
     * GTK bermasalah dicatat gagal dengan alasan; sisanya tetap diproses.
     *
     * @return array{succeeded: int, failed: int, failures: array<int, string>}
     */
    public function processBatch(DecreeBatch $batch, User $actor): array
    {
        $decrees = $batch->decrees()->with('employee')->get();

        $succeeded = 0;
        $failed = 0;
        $failures = [];

        foreach ($decrees as $decree) {
            try {
                $issue = $this->validateRecipient($decree->employee);

                if ($issue !== null) {
                    throw new RuntimeException($issue);
                }

                $this->workflow->transition($decree, DecreeStatus::Submitted, $actor);
                $this->workflow->transition($decree->fresh(), DecreeStatus::Verified, $actor);

                $succeeded++;
            } catch (RuntimeException $e) {
                $failed++;

                $failures[] = $decree->employee?->name.' ('.$decree->employee?->nigy.'): '.$e->getMessage();

                $decree->update(['status' => DecreeStatus::Rejected, 'rejection_reason' => $e->getMessage()]);
            }
        }

        $batch->update([
            'succeeded' => $succeeded,
            'failed' => $failed,
            'status' => DecreeBatchStatus::AwaitingSignature,
        ]);

        return ['succeeded' => $succeeded, 'failed' => $failed, 'failures' => $failures];
    }

    /**
     * Tandatangani seluruh SK verified dalam batch (satu klik, PRD F5.22).
     * Kegagalan satu item tidak menggagalkan item lain (F5.24).
     *
     * @return array{succeeded: int, failed: int, failures: array<int, string>}
     */
    public function signBatch(DecreeBatch $batch, User $actor): array
    {
        $decrees = $batch->decrees()
            ->where('status', DecreeStatus::Verified->value)
            ->with('employee')
            ->get();

        $succeeded = 0;
        $failed = 0;
        $failures = [];

        foreach ($decrees as $decree) {
            try {
                $this->workflow->transition($decree, DecreeStatus::Issued, $actor);
                $this->issueService->issue($decree->fresh(), $actor);

                $succeeded++;
            } catch (RuntimeException $e) {
                $failed++;

                $failures[] = $decree->employee?->name.' ('.$decree->employee?->nigy.'): '.$e->getMessage();
            }
        }

        $batch->update([
            'succeeded' => $succeeded,
            'failed' => $failed,
            'status' => DecreeBatchStatus::Completed,
            'signed_by' => $actor->id,
            'signed_at' => now(),
        ]);

        return ['succeeded' => $succeeded, 'failed' => $failed, 'failures' => $failures];
    }

    /**
     * Batalkan batch selama belum ditandatangani (F5.25).
     */
    public function cancelBatch(DecreeBatch $batch, User $actor): void
    {
        if ($batch->status->value !== DecreeBatchStatus::Preparing->value
            && $batch->status->value !== DecreeBatchStatus::AwaitingSignature->value) {
            throw new RuntimeException('Batch yang sudah ditandatangani tidak dapat dibatalkan.');
        }

        $batch->decrees()->update(['status' => DecreeStatus::Cancelled->value, 'cancellation_reason' => 'Batch dibatalkan']);

        $batch->update(['status' => DecreeBatchStatus::Cancelled]);
    }

    protected function validateRecipient(Employee $employee): ?string
    {
        if (! $employee->is_active) {
            return 'GTK tidak aktif.';
        }

        if (! $employee->position_id) {
            return 'Jabatan belum diisi.';
        }

        if (! $employee->foundation_start_date) {
            return 'TMT Yayasan belum diisi.';
        }

        if (! $employee->educations()->where('is_highest', true)->exists()) {
            return 'Pendidikan tertinggi belum diisi.';
        }

        return null;
    }

    public static function canProcess(User $user): bool
    {
        return $user->role === UserRole::FoundationAdmin;
    }
}
