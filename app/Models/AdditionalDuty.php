<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\AdditionalDutyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property array<string, mixed>|null $applicable_levels
 * @property string|null $hour_equivalence
 * @property int|null $quota_per_unit
 * @property bool $requires_decree
 * @property bool $is_active
 *
 * @use HasFactory<AdditionalDutyFactory>
 */
class AdditionalDuty extends Model
{
    use Auditable;

    /** @use HasFactory<AdditionalDutyFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'applicable_levels', 'hour_equivalence',
        'quota_per_unit', 'requires_decree', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'applicable_levels' => 'array',
            'hour_equivalence' => 'decimal:1',
            'requires_decree' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class);
    }
}
