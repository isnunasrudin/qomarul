<?php

use App\Enums\DecreeBatchStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decree_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('decree_type_id')->constrained();
            $table->string('academic_year', 9);
            $table->date('effective_date');
            $table->date('issued_date');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('succeeded')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->string('status')->default(DecreeBatchStatus::Preparing->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decree_batches');
    }
};
