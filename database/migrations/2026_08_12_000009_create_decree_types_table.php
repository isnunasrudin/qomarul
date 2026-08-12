<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decree_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('template_view')->nullable();
            $table->string('number_format')->nullable();
            $table->unsignedTinyInteger('number_padding')->default(3);
            $table->text('consideration_recalling')->nullable();
            $table->json('consideration_weighing')->nullable();
            $table->text('consideration_observing')->nullable();
            $table->boolean('requires_effective_date')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decree_types');
    }
};
