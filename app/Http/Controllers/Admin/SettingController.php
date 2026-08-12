<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Setting::class);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'foundation' => $this->defaults('foundation'),
                'letterhead' => $this->defaults('letterhead'),
                'nigy' => $this->defaults('nigy'),
            ],
            'schema' => $this->schema(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $rules = collect($this->schema())
            ->mapWithKeys(fn ($field, $key) => [$key => $field['rules']])
            ->all();

        $data = $request->validate($rules);

        foreach ($this->schema() as $key => $field) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            Setting::set($key, $data[$key], $field['group']);
        }

        return back()->with('success', __('common.updated'));
    }

    /**
     * Unggah gambar tanda tangan basah (PRD F7.16–F7.20).
     * Hanya Admin Yayasan, konfirmasi ulang kata sandi, izin 0400,
     * tercatat di audit log.
     */
    public function updateSignature(Request $request): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'file' => ['required', 'file', 'max:2048'],
        ]);

        $file = $request->file('file');

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file->getContent());

        if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return back()->withErrors(['file' => 'Gambar tanda tangan harus PNG atau JPG.'])->withInput();
        }

        $path = $file->storeAs('signature', 'signature-basah.png', 'private');

        if (function_exists('chmod')) {
            @chmod(Storage::disk('private')->path($path), 0400);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'signature_replaced',
            'auditable_type' => Setting::class,
            'auditable_id' => 'foundation.signature_path',
            'new_values' => ['path' => $path],
        ]);

        Setting::set('foundation.signature_path', $path, 'foundation');

        return back()->with('success', 'Gambar tanda tangan diganti dan disimpan secara privat (izin 0400).');
    }

    /** @return array<string, mixed> */
    protected function defaults(string $group): array
    {
        $defaults = [
            'foundation' => [
                'name' => 'Yayasan Pondok Pesantren Qomarul Hidayah',
                'address' => '',
                'notary_deed' => '',
                'sk_menkumham' => '',
                'chairman_name' => '',
                'chairman_position' => 'Ketua Yayasan',
                'default_issued_place' => 'Gondang',
                'logo_path' => null,
            ],
            'letterhead' => [
                'cc_list' => [],
            ],
            'nigy' => [
                'format' => '{tahun_masuk}{kode_satker}{urut}',
                'padding' => 3,
            ],
        ];

        $stored = Setting::where('group', $group)->get()
            ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->value])
            ->all();

        return array_merge($defaults[$group], $stored);
    }

    /** @return array<string, array<string, mixed>> */
    protected function schema(): array
    {
        return [
            'foundation.name' => ['rules' => ['required', 'string', 'max:255'], 'group' => 'foundation'],
            'foundation.address' => ['rules' => ['nullable', 'string'], 'group' => 'foundation'],
            'foundation.notary_deed' => ['rules' => ['nullable', 'string', 'max:255'], 'group' => 'foundation'],
            'foundation.sk_menkumham' => ['rules' => ['nullable', 'string', 'max:255'], 'group' => 'foundation'],
            'foundation.chairman_name' => ['rules' => ['required', 'string', 'max:255'], 'group' => 'foundation'],
            'foundation.chairman_position' => ['rules' => ['required', 'string', 'max:255'], 'group' => 'foundation'],
            'foundation.default_issued_place' => ['rules' => ['required', 'string', 'max:255'], 'group' => 'foundation'],
            'letterhead.cc_list' => ['rules' => ['nullable', 'array'], 'group' => 'letterhead'],
            'letterhead.cc_list.*' => ['rules' => ['string', 'max:255'], 'group' => 'letterhead'],
            'nigy.format' => ['rules' => ['required', 'string', 'max:100'], 'group' => 'nigy'],
            'nigy.padding' => ['rules' => ['required', 'integer', 'min:1', 'max:10'], 'group' => 'nigy'],
        ];
    }
}
