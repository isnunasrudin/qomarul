<?php

namespace App\Models;

use App\Enums\DecreeBatchStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\FormatsDatesLocally;
use Database\Factories\DecreeBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property int $decree_type_id
 * @property string $academic_year
 * @property Carbon|null $effective_date
 * @property Carbon|null $issued_date
 * @property int $total
 * @property int $succeeded
 * @property int $failed
 * @property DecreeBatchStatus $status
 * @property int|null $created_by
 * @property int|null $signed_by
 * @property Carbon|null $signed_at
 *
 * @use HasFactory<DecreeBatchFactory>
 */
class DecreeBatch extends Model
{
    use Auditable;
    use FormatsDatesLocally;

    /** @use HasFactory<DecreeBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'decree_type_id', 'academic_year', 'effective_date', 'issued_date',
        'total', 'succeeded', 'failed', 'status', 'created_by', 'signed_by', 'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date:Y-m-d',
            'issued_date' => 'date:Y-m-d',
            'signed_at' => 'datetime',
            'status' => DecreeBatchStatus::class,
        ];
    }

    /** @return BelongsTo<DecreeType, $this> */
    public function decreeType(): BelongsTo
    {
        return $this->belongsTo(DecreeType::class);
    }

    /** @return HasMany<Decree, $this> */
    public function decrees(): HasMany
    {
        return $this->hasMany(Decree::class);
    }
}
