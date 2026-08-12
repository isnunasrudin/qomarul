<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decree_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decree_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('signer_name');
            $table->timestamp('signed_at')->nullable();
            $table->string('hash_sha256', 64);
            $table->json('signature_meta')->nullable();
            $table->timestamps();

            $table->unique('decree_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decree_signatures');
    }
};
