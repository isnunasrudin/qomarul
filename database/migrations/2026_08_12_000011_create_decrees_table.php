<?php

use App\Enums\DecreeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decrees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('decree_type_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('work_unit_id')->constrained();
            $table->foreignId('decree_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('decree_number')->nullable()->unique();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->string('registration_number')->nullable()->unique();
            $table->string('academic_year', 9)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('issued_place')->nullable();
            $table->string('appointed_as')->nullable();
            $table->string('position_snapshot')->nullable();
            $table->json('snapshot_data')->nullable();
            $table->string('status')->default(DecreeStatus::Draft->value);
            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('replacement_decree_id')->nullable()->constrained('decrees')->nullOnDelete();
            $table->boolean('is_legacy')->default(false);
            $table->timestamp('legacy_verified_at')->nullable();
            $table->unsignedBigInteger('legacy_verified_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['work_unit_id', 'status']);
            $table->index(['employee_id', 'academic_year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decrees');
    }
};
