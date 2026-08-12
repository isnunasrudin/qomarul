<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property string $p12_path
 * @property string $password_encrypted
 * @property string $subject
 * @property string $issuer
 * @property string $serial
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property string $fingerprint
 * @property bool $is_active
 */
class Certificate extends Model
{
    use Auditable;

    protected $fillable = [
        'name', 'p12_path', 'password_encrypted', 'subject', 'issuer', 'serial',
        'valid_from', 'valid_until', 'fingerprint', 'is_active',
    ];

    protected $hidden = ['password_encrypted', 'p12_path'];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<DecreeSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(DecreeSignature::class);
    }
}
