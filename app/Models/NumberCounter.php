<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $key
 * @property int $year
 * @property int $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class NumberCounter extends Model
{
    protected $fillable = [
        'key', 'year', 'value',
    ];
}
