<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('level');
            $table->string('institution');
            $table->string('major')->nullable();
            $table->string('start_year', 4)->nullable();
            $table->string('end_year', 4)->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('certificate_date')->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->boolean('is_highest')->default(false);
            $table->string('certificate_file_path')->nullable();
            $table->string('transcript_file_path')->nullable();
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educations');
    }
};
