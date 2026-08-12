<?php

namespace App\Models;

use Database\Factories\EmploymentStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property bool $is_active
 *
 * @use HasFactory<EmploymentStatusFactory>
 */
class EmploymentStatus extends Model
{
    /** @use HasFactory<EmploymentStatusFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
