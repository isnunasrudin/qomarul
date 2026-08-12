<?php

namespace App\Services\Decree;

use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeWorkflowLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mesin status SK (PRD §5.5.1) dengan transisi legal eksplisit.
 *
 *   draft → submitted → verified → issued
 *     ^        │            │
 *     │<─rejected<───────────┘
 *   issued → cancelled / superseded
 *
 * Nomor SK & registrasi dialokasikan HANYA pada transisi draft/submitted → verified.
 * Setiap transisi menulis decree_workflow_logs.
 */
class DecreeWorkflowService
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['verified', 'rejected'],
        'rejected' => ['submitted', 'draft'],
        'verified' => ['issued', 'rejected'],
        'issued' => ['cancelled', 'superseded'],
        'cancelled' => [],
        'superseded' => [],
    ];

    /** @var array<string, string> */
    private const ACTOR_ROLES = [
        'draft' => 'submit',
        'submitted' => 'submit',
        'verified' => 'verify',
        'rejected' => 'reject',
        'issued' => 'sign',
        'cancelled' => 'cancel',
        'superseded' => 'cancel',
    ];

    public function __construct(private readonly DecreeNumberService $numberService) {}

    public function canTransition(Decree $decree, DecreeStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$decree->status->value];

        return in_array($to->value, $allowed, true);
    }

    /**
     * Jalankan transisi legal; alokasikan nomor saat verified; tulis log.
     */
    public function transition(Decree $decree, DecreeStatus $to, User $actor, ?string $notes = null): Decree
    {
        $from = $decree->status;

        if (! $this->canTransition($decree, $to)) {
            throw new RuntimeException(
                "Transisi ilegal: {$from->value} → {$to->value}.",
            );
        }

        if (! $actor->can(self::ACTOR_ROLES[$to->value], $decree)) {
            throw new RuntimeException('Peran tidak berhak melakukan transisi ini.');
        }

        return DB::transaction(function () use ($decree, $to, $actor, $notes, $from): Decree {
            $data = ['status' => $to];

            if ($to === DecreeStatus::Verified) {
                $numbers = $this->numberService->allocate($decree);

                $data = array_merge($data, [
                    'decree_number' => $numbers['decree_number'],
                    'sequence_number' => $numbers['sequence_number'],
                    'registration_number' => $numbers['registration_number'],
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
            }

            if ($to === DecreeStatus::Issued) {
                $data['signed_by'] = $actor->id;
                $data['signed_at'] = now();
            }

            if ($to === DecreeStatus::Rejected) {
                if (blank($notes)) {
                    throw new RuntimeException('Penolakan wajib menyertakan alasan.');
                }

                $data['rejection_reason'] = $notes;
            }

            if ($to === DecreeStatus::Cancelled) {
                if (blank($notes)) {
                    throw new RuntimeException('Pembatalan wajib menyertakan alasan.');
                }

                $data['cancellation_reason'] = $notes;
            }

            $decree->update($data);

            DecreeWorkflowLog::create([
                'decree_id' => $decree->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'user_id' => $actor->id,
                'notes' => $notes,
            ]);

            return $decree->fresh();
        });
    }

    /**
     * Terbitkan SK pengganti: SK lama ditandai superseded dan menunjuk
     * penggantinya (PRD F5.7).
     */
    public function issueReplacement(Decree $replacement, Decree $superseded, User $actor): Decree
    {
        $this->transition($superseded, DecreeStatus::Superseded, $actor, "Digantikan oleh SK {$replacement->decree_number}");

        $superseded->update(['replacement_decree_id' => $replacement->id]);

        return $this->transition($replacement, DecreeStatus::Issued, $actor, 'SK pengganti');
    }

    /** @return array<string, array<int, string>> */
    public static function legalTransitions(): array
    {
        return self::TRANSITIONS;
    }
}
