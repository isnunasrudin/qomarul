<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

/**
 * Hapus baris master data dengan aman.
 *
 * Foreign key di skema ini bersifat restrict, jadi menghapus baris yang masih
 * dipakai akan melempar galat SQL. Trait ini mengubahnya menjadi pesan yang
 * bisa dibaca pengguna, dan hanya menelan pelanggaran integritas — galat lain
 * tetap dilempar supaya tidak tersembunyi.
 */
trait DeletesMasterData
{
    protected function deleteMasterData(Model $model, string $label): RedirectResponse
    {
        try {
            $this->deleteRow($model);
        } catch (QueryException $exception) {
            // 23000 = pelanggaran integritas (MySQL/MariaDB, SQLite), 23503 = FK (PostgreSQL).
            if (! in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                throw $exception;
            }

            return back()->with(
                'error',
                $label.' tidak bisa dihapus karena masih dipakai data lain. Nonaktifkan saja bila sudah tidak dipakai.',
            );
        }

        return back()->with('success', __('common.deleted'));
    }

    /**
     * Dipisah agar anotasi @throws terbaca analisis statis: Eloquent tidak
     * mendeklarasikan QueryException padahal lapisan DB bisa melemparnya.
     *
     * @throws QueryException
     */
    protected function deleteRow(Model $model): void
    {
        $model->delete();
    }
}
