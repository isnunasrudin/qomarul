<?php

namespace App\Exports;

use App\Exports\Sheets\UserImportGuideSheet;
use App\Exports\Sheets\UserImportSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Template Excel impor pengguna: sheet "Pengguna" (judul kolom saja) dan
 * sheet "Petunjuk" berisi penjelasan tiap kolom.
 */
class UserTemplateExport implements WithMultipleSheets
{
    /** @return array<int, object> */
    public function sheets(): array
    {
        return [
            new UserImportSheet,
            new UserImportGuideSheet,
        ];
    }
}
