<?php

namespace App\Models;

use App\Enums\DecreeStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DecreeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $uuid
 * @property int $decree_type_id
 * @property int $employee_id
 * @property int $work_unit_id
 * @property int|null $decree_batch_id
 * @property string $decree_number
 * @property string $sequence_number
 * @property string $registration_number
 * @property string $academic_year
 * @property Carbon|null $effective_date
 * @property Carbon|null $issued_date
 * @property string $issued_place
 * @property string $appointed_as
 * @property string $position_snapshot
 * @property array<string, mixed>|null $snapshot_data
 * @property DecreeStatus $status
 * @property string $pdf_path
 * @property string $pdf_hash
 * @property string $rejection_reason
 * @property string $cancellation_reason
 * @property int|null $replacement_decree_id
 * @property bool $is_legacy
 * @property Carbon|null $legacy_verified_at
 * @property int|null $legacy_verified_by
 * @property int|null $created_by
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property int|null $signed_by
 * @property Carbon|null $signed_at
 *
 * @use HasFactory<DecreeFactory>
 */
class Decree extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DecreeFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid', 'decree_type_id', 'employee_id', 'work_unit_id', 'decree_batch_id',
        'decree_number', 'sequence_number', 'registration_number', 'academic_year',
        'effective_date', 'issued_date', 'issued_place', 'appointed_as',
        'position_snapshot', 'snapshot_data', 'status', 'pdf_path', 'pdf_hash',
        'rejection_reason', 'cancellation_reason', 'replacement_decree_id',
        'is_legacy', 'legacy_verified_at', 'legacy_verified_by',
        'created_by', 'verified_by', 'verified_at', 'signed_by', 'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'issued_date' => 'date',
            'snapshot_data' => 'array',
            'status' => DecreeStatus::class,
            'verified_at' => 'datetime',
            'legacy_verified_at' => 'datetime',
            'signed_at' => 'datetime',
            'is_legacy' => 'boolean',
        ];
    }

    /** @return BelongsTo<DecreeType, $this> */
    public function decreeType(): BelongsTo
    {
        return $this->belongsTo(DecreeType::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<WorkUnit, $this> */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /** @return BelongsTo<DecreeBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(DecreeBatch::class, 'decree_batch_id');
    }

    /** @return BelongsTo<Decree, $this> */
    public function replacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replacement_decree_id');
    }

    /** @return HasOne<DecreeSignature, $this> */
    public function signature(): HasOne
    {
        return $this->hasOne(DecreeSignature::class);
    }

    /** @return HasMany<DecreeWorkflowLog, $this> */
    public function workflowLogs(): HasMany
    {
        return $this->hasMany(DecreeWorkflowLog::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return BelongsTo<User, $this> */
    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
