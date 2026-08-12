<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $employee_id
 * @property string $category
 * @property string $name
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property int|null $uploaded_by
 *
 * @use HasFactory<DocumentFactory>
 */
/**
 * @property int $id
 * @property int $employee_id
 * @property string $category
 * @property string $name
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property int|null $uploaded_by
 * @property string|null $signed_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @use HasFactory<DocumentFactory>
 */
class Document extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id', 'category', 'name', 'path', 'mime', 'size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
