<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DecreeTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property string $template_view
 * @property string $number_format
 * @property int $number_padding
 * @property string $consideration_recalling
 * @property array<string, mixed>|null $consideration_weighing
 * @property string $consideration_observing
 * @property bool $requires_effective_date
 * @property bool $is_active
 *
 * @use HasFactory<DecreeTypeFactory>
 */
class DecreeType extends Model
{
    use Auditable;

    /** @use HasFactory<DecreeTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'template_view', 'number_format', 'number_padding',
        'consideration_recalling', 'consideration_weighing',
        'consideration_observing', 'requires_effective_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'consideration_weighing' => 'array',
            'number_padding' => 'integer',
            'requires_effective_date' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Decree, $this> */
    public function decrees(): HasMany
    {
        return $this->hasMany(Decree::class);
    }
}
