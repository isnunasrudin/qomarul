<?php

use App\Enums\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default(DocumentCategory::Other->value);
            $table->string('name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedInteger('size');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
