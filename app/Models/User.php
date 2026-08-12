<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property string $email
 * @property string $username
 * @property string $password
 * @property UserRole $role
 * @property int|null $work_unit_id
 * @property int|null $employee_id
 * @property bool $is_active
 * @property bool $must_change_password
 * @property string|null $two_factor_secret
 * @property bool $two_factor_enabled
 * @property bool $can_impersonate
 * @property Carbon|null $last_login_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @use HasFactory<UserFactory>
 */
#[Fillable([
    'name',
    'email',
    'username',
    'password',
    'role',
    'work_unit_id',
    'employee_id',
    'is_active',
    'must_change_password',
    'two_factor_secret',
    'two_factor_enabled',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string> */
    protected $appends = ['two_factor_active'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /** 2FA benar-benar aktif hanya bila setup selesai (secret tersimpan). */
    public function getTwoFactorActiveAttribute(): bool
    {
        return $this->two_factor_enabled && (bool) $this->two_factor_secret;
    }

    /** @return BelongsTo<WorkUnit, $this> */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
