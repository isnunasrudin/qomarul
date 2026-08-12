<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property string $year
 * @property array<string, mixed>|null $value
 */
class NumberCounter extends Model
{
    protected $fillable = [
        'key', 'year', 'value',
    ];
}
