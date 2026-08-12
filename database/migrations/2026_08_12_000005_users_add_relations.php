<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('role')->default(UserRole::Employee->value)->after('password');
            $table->foreignId('work_unit_id')->nullable()->after('role')
                ->constrained('work_units')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->after('work_unit_id')
                ->constrained('employees')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('employee_id');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->string('two_factor_secret')->nullable()->after('must_change_password');
            $table->index(['role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_unit_id');
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn(['username', 'role', 'is_active', 'must_change_password', 'two_factor_secret']);
        });
    }
};
