<?php

namespace App\Http\Controllers\Public;

use App\Enums\DecreeStatus;
use App\Http\Controllers\Controller;
use App\Models\Decree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verifikasi publik dokumen ber-UUID (PRD F7.7–F7.11) — tanpa login,
 * tanpa Inertia auth. Data minimum saja, tanpa data sensitif.
 */
class VerificationController extends Controller
{
    public function show(string $uuid): View
    {
        $decree = Decree::query()
            ->where('uuid', $uuid)
            ->with(['decreeType:id,code,name', 'replacement:id,decree_number,status'])
            ->first();

        if (! $decree) {
            abort(404);
        }

        $snapshot = $decree->snapshot_data ?? [];

        $replacementNumber = null;
        if ($decree->status === DecreeStatus::Superseded) {
            $replacementNumber = $decree->replacement?->decree_number;
        }

        return view('verification.show', [
            'decree' => $decree,
            'snapshot' => $snapshot,
            'replacementNumber' => $replacementNumber,
            'signature' => $decree->signature,
        ]);
    }

    /**
     * Verifikasi mandiri: unggah PDF → bandingkan hash (PRD F7.10).
     */
    public function verifyFile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'uuid' => ['required', 'uuid'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $decree = Decree::where('uuid', $data['uuid'])->first();

        if (! $decree || ! $decree->pdf_hash) {
            return back()->withErrors(['file' => 'Dokumen tidak ditemukan atau belum diterbitkan.']);
        }

        $hash = hash('sha256', $request->file('file')->getContent());
        $matches = hash_equals($decree->pdf_hash, $hash);

        return back()->with('result', [
            'matches' => $matches,
            'expected' => $decree->pdf_hash,
            'provided' => $hash,
            'decree_number' => $decree->decree_number,
            'uuid' => $decree->uuid,
        ]);
    }
}
