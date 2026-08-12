<?php

namespace App\Models\Scopes;

use App\Enums\UserRole;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/** @implements Scope<Model> */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->role === UserRole::UnitAdmin) {
            $builder->where($model->getTable().'.work_unit_id', $user->work_unit_id);

            return;
        }

        if ($user->role === UserRole::Employee) {
            $column = $model instanceof Employee ? 'id' : 'employee_id';

            $builder->where($model->getTable().'.'.$column, $user->employee_id);
        }
    }
}
