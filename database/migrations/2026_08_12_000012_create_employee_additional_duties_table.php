<?php

use App\Enums\AdditionalDutyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_additional_duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('additional_duty_id')->constrained();
            $table->foreignId('work_unit_id')->constrained();
            $table->string('academic_year', 9);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('notes')->nullable();
            $table->foreignId('decree_id')->nullable()->constrained('decrees')->nullOnDelete();
            $table->string('status')->default(AdditionalDutyStatus::Active->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'additional_duty_id', 'academic_year'], 'ead_unique_employee_duty_year');
            $table->index(['work_unit_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_additional_duties');
    }
};
