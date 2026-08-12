<?php

namespace App\Models;

use App\Enums\WorkUnitLevel;
use Database\Factories\WorkUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property WorkUnitLevel $level
 * @property string $npsn
 * @property string $address
 * @property string $head_name
 * @property string $head_nigy
 * @property string $phone
 * @property string $email
 * @property string $logo_path
 * @property bool $is_active
 *
 * @use HasFactory<WorkUnitFactory>
 */
class WorkUnit extends Model
{
    /** @use HasFactory<WorkUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'level', 'npsn', 'address', 'head_name', 'head_nigy',
        'phone', 'email', 'logo_path', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => WorkUnitLevel::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
