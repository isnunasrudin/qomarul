<?php

namespace App\Models;

use App\Enums\EducationLevel;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\FormatsDatesLocally;
use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $employee_id
 * @property EducationLevel $level
 * @property string $institution
 * @property string $major
 * @property string $start_year
 * @property string $end_year
 * @property string $certificate_number
 * @property Carbon|null $certificate_date
 * @property string|null $gpa
 * @property bool $is_highest
 * @property string $certificate_file_path
 * @property string $transcript_file_path
 *
 * @use HasFactory<EducationFactory>
 */
class Education extends Model
{
    use Auditable;
    use BelongsToTenant;
    use FormatsDatesLocally;

    /** @use HasFactory<EducationFactory> */
    use HasFactory;

    protected $table = 'educations';

    protected $fillable = [
        'employee_id', 'level', 'institution', 'major', 'start_year', 'end_year',
        'certificate_number', 'certificate_date', 'gpa', 'is_highest',
        'certificate_file_path', 'transcript_file_path',
    ];

    protected function casts(): array
    {
        return [
            'level' => EducationLevel::class,
            'certificate_date' => 'date:Y-m-d',
            'is_highest' => 'boolean',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
