<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int|null $decree_id
 * @property int|null $certificate_id
 * @property string $signer_name
 * @property Carbon|null $signed_at
 * @property string $hash_sha256
 * @property array<string, mixed>|null $signature_meta
 */
class DecreeSignature extends Model
{
    protected $fillable = [
        'decree_id', 'certificate_id', 'signer_name', 'signed_at',
        'hash_sha256', 'signature_meta',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'signature_meta' => 'array',
        ];
    }

    /** @return BelongsTo<Decree, $this> */
    public function decree(): BelongsTo
    {
        return $this->belongsTo(Decree::class);
    }

    /** @return BelongsTo<Certificate, $this> */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
