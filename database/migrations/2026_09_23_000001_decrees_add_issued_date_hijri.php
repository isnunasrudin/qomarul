<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decrees', function (Blueprint $table) {
            // Tanggal Hijriah siap-cetak, mis. "25 Muharam 1448 H". Disimpan sebagai
            // teks karena bisa dikoreksi operator dan tidak selalu sama dengan hasil
            // algoritma (penetapan Kemenag lewat sidang isbat).
            $table->string('issued_date_hijri', 50)->nullable()->after('issued_date');
        });
    }

    public function down(): void
    {
        Schema::table('decrees', function (Blueprint $table) {
            $table->dropColumn('issued_date_hijri');
        });
    }
};
