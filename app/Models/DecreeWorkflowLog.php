<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $decree_id
 * @property string $from_status
 * @property string $to_status
 * @property int|null $user_id
 * @property string $notes
 */
class DecreeWorkflowLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'decree_id', 'from_status', 'to_status', 'user_id', 'notes',
    ];

    /** @return BelongsTo<Decree, $this> */
    public function decree(): BelongsTo
    {
        return $this->belongsTo(Decree::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
