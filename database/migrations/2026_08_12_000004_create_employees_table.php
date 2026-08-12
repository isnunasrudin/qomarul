<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nigy')->unique();
            $table->string('nik', 16)->nullable()->index();
            $table->string('nuptk', 16)->nullable();
            $table->string('nip', 18)->nullable();
            $table->string('title_prefix')->nullable();
            $table->string('name');
            $table->string('title_suffix')->nullable();
            $table->string('gender');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('mother_name')->nullable();
            $table->text('address')->nullable();
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('regency')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 5)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('npwp', 20)->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('work_unit_id')->constrained();
            $table->foreignId('position_id')->constrained();
            $table->foreignId('employment_status_id')->constrained();
            $table->date('foundation_start_date')->nullable();
            $table->date('unit_start_date')->nullable();
            $table->string('subject')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('work_unit_id');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText('name');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
