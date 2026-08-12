<?php

namespace App\Models;

use App\Enums\PositionGroup;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property string $group
 * @property bool $is_active
 *
 * @use HasFactory<PositionFactory>
 */
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'group', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'group' => PositionGroup::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
