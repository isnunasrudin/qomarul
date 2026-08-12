<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Decree;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Antrean verifikasi arsip SK lama yang diunggah GTK (PRD F3.18/F5.33).
 */
class LegacyDecreeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Decree::class);

        $decrees = Decree::query()
            ->where('is_legacy', true)
            ->with(['employee:id,nigy,name,work_unit_id', 'decreeType:id,code,name'])
            ->orderBy('legacy_verified_at', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/DecreeLegacy/Index', [
            'decrees' => $decrees,
        ]);
    }

    public function verify(Decree $decree): RedirectResponse
    {
        $this->authorize('verifyLegacy', $decree);

        $decree->update([
            'legacy_verified_at' => now(),
            'legacy_verified_by' => request()->user()->id,
        ]);

        return back()->with('success', 'Arsip SK diverifikasi dan masuk riwayat resmi.');
    }

    public function destroy(Decree $decree): RedirectResponse
    {
        $this->authorize('deleteLegacy', $decree);

        Storage::disk('private')->delete($decree->pdf_path);
        $decree->delete();

        return back()->with('success', __('common.deleted'));
    }
}
