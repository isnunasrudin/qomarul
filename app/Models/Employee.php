<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\FormatsDatesLocally;
use App\Support\TitleFormatter;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $nigy
 * @property string $nik
 * @property string $nuptk
 * @property string $nip
 * @property string $title_prefix
 * @property string $name
 * @property string $title_suffix
 * @property Gender $gender
 * @property string $birth_place
 * @property Carbon|null $birth_date
 * @property string $religion
 * @property string $marital_status
 * @property string $mother_name
 * @property string $address
 * @property string $rt
 * @property string $rw
 * @property string $village
 * @property string $district
 * @property string $regency
 * @property string $province
 * @property string $postal_code
 * @property string $phone
 * @property string $email
 * @property string $npwp
 * @property string $bank_account_number
 * @property string $bank_name
 * @property string $blood_type
 * @property string $photo_path
 * @property int $work_unit_id
 * @property int $position_id
 * @property int $employment_status_id
 * @property Carbon|null $foundation_start_date
 * @property Carbon|null $unit_start_date
 * @property string $subject
 * @property bool $is_active
 * @property bool $can_impersonate
 * @property Carbon|null $termination_date
 * @property string $termination_reason
 *
 * @use HasFactory<EmployeeFactory>
 */
class Employee extends Model
{
    use Auditable;
    use BelongsToTenant;
    use FormatsDatesLocally;

    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'nigy', 'nik', 'nuptk', 'nip', 'title_prefix', 'name', 'title_suffix',
        'gender', 'birth_place', 'birth_date', 'religion', 'marital_status',
        'mother_name', 'address', 'rt', 'rw', 'village', 'district', 'regency',
        'province', 'postal_code', 'phone', 'email', 'npwp',
        'bank_account_number', 'bank_name', 'blood_type', 'photo_path',
        'work_unit_id', 'position_id', 'employment_status_id',
        'foundation_start_date', 'unit_start_date', 'subject',
        'is_active', 'termination_date', 'termination_reason',
    ];

    protected $appends = ['full_name'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'foundation_start_date' => 'date:Y-m-d',
            'unit_start_date' => 'date:Y-m-d',
            'termination_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<WorkUnit, $this> */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** @return BelongsTo<EmploymentStatus, $this> */
    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    /** @return HasMany<Education, $this> */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<Decree, $this> */
    public function decrees(): HasMany
    {
        return $this->hasMany(Decree::class);
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function additionalDuties(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class);
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function highestEducation(): ?Education
    {
        return $this->educations()->where('is_highest', true)->first();
    }

    public function getFullNameAttribute(): string
    {
        $prefix = trim((string) $this->title_prefix);
        $suffix = trim((string) $this->title_suffix);
        $full = trim($prefix.' '.$this->name);

        if ($suffix !== '') {
            $full .= ', '.$suffix;
        }

        return $full;
    }

    public function setTitlePrefixAttribute(?string $value): void
    {
        $this->attributes['title_prefix'] = TitleFormatter::normalizePrefix($value);
    }

    public function setTitleSuffixAttribute(?string $value): void
    {
        $this->attributes['title_suffix'] = TitleFormatter::normalizeSuffix($value);
    }
}
