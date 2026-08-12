<?php

namespace App\Models;

use App\Enums\AdditionalDutyStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\FormatsDatesLocally;
use Database\Factories\EmployeeAdditionalDutyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $employee_id
 * @property int $additional_duty_id
 * @property int $work_unit_id
 * @property string $academic_year
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string $notes
 * @property int|null $decree_id
 * @property AdditionalDutyStatus $status
 * @property int|null $created_by
 *
 * @use HasFactory<EmployeeAdditionalDutyFactory>
 */
class EmployeeAdditionalDuty extends Model
{
    use Auditable;
    use BelongsToTenant;
    use FormatsDatesLocally;

    /** @use HasFactory<EmployeeAdditionalDutyFactory> */
    use HasFactory;

    protected $table = 'employee_additional_duties';

    protected $fillable = [
        'employee_id', 'additional_duty_id', 'work_unit_id', 'academic_year',
        'start_date', 'end_date', 'notes', 'decree_id', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'status' => AdditionalDutyStatus::class,
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<AdditionalDuty, $this> */
    public function additionalDuty(): BelongsTo
    {
        return $this->belongsTo(AdditionalDuty::class);
    }

    /** @return BelongsTo<WorkUnit, $this> */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /** @return BelongsTo<Decree, $this> */
    public function decree(): BelongsTo
    {
        return $this->belongsTo(Decree::class);
    }
}
