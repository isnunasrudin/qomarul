<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model): void {
            static::recordAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function ($model): void {
            if (! $model->getChanges()) {
                return;
            }

            $changes = $model->getChanges();

            if (isset($changes['updated_at'])) {
                unset($changes['updated_at']);
            }

            if (! $changes) {
                return;
            }

            $old = [];
            $new = [];

            foreach ($changes as $key => $value) {
                if (in_array($key, $model->getHidden(), true)) {
                    continue;
                }

                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            static::recordAudit($model, 'updated', $old, $new);
        });

        static::deleted(function ($model): void {
            static::recordAudit($model, 'deleted', $model->getOriginal(), []);
        });
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    protected static function recordAudit(
        Model $model,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $request = app('request');

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
    }
}
