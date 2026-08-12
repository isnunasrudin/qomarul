<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_duties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->json('applicable_levels')->nullable();
            $table->decimal('hour_equivalence', 5, 1)->nullable();
            $table->unsignedSmallInteger('quota_per_unit')->nullable();
            $table->boolean('requires_decree')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_duties');
    }
};
