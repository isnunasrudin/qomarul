<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;

/**
 * Regresi BUG-03/BUG-04: nilai tersimpan harus tampil di form Pengaturan
 * Yayasan, dan menekan Simpan harus benar-benar menulis ke tabel `settings`.
 *
 * Catatan: payload dikirim bersarang karena PHP mengubah titik pada nama field
 * form menjadi underscore ("foundation.name" -> "foundation_name"), sehingga
 * kunci bertitik tidak pernah sampai ke validator.
 */
it('mengirim tembusan tersimpan dengan kunci pendek ke halaman pengaturan', function () {
    $this->seed(SettingSeeder::class);

    $admin = User::factory()->foundationAdmin()->create();

    $response = $this->actingAs($admin)->get(route('admin.settings.index'));
    $response->assertOk();

    $page = $response->viewData('page');
    $letterhead = $page['props']['settings']['letterhead'];

    // Sebelum perbaikan: ['cc_list' => [], 'letterhead.cc_list' => [5 item]]
    // sehingga textarea "Daftar Tembusan Default" selalu kosong.
    expect(array_keys($letterhead))->toBe(['cc_list'])
        ->and($letterhead['cc_list'])->toHaveCount(5)
        ->and($letterhead['cc_list'][0])->toBe('Kepala Dinas Pendidikan Pemuda dan Olah Raga Kab. Trenggalek')
        ->and($letterhead['cc_list'][4])->toBe('Arsip');

    // Nilai tersimpan menang atas default hard-coded, bukan sebaliknya.
    $foundation = $page['props']['settings']['foundation'];
    expect($foundation['address'])->toBe('Gondang, Tugu, Trenggalek, Jawa Timur')
        ->and($foundation['notary_deed'])->toBe('KAYUN WIDIHARSONO, S.H, M.Kn — Nomor: 09 Tahun 2014')
        ->and($foundation['sk_menkumham'])->toBe('C-598.HT.03.01-2014')
        ->and(array_keys($foundation))->not->toContain('foundation.address');
});

it('menyimpan pengaturan dan tembusan dari payload bersarang', function () {
    $admin = User::factory()->foundationAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'foundation' => [
                'name' => 'Yayasan Pondok Pesantren Qomarul Hidayah',
                'address' => 'Gondang, Tugu, Trenggalek, Jawa Timur',
                'notary_deed' => 'KAYUN WIDIHARSONO, S.H, M.Kn — Nomor: 09 Tahun 2014',
                'sk_menkumham' => 'C-598.HT.03.01-2014',
                'chairman_name' => 'Hj. Zumrotun Nasihah',
                'chairman_position' => 'Ketua Yayasan',
                'default_issued_place' => 'Trenggalek',
                // di luar skema: logo diunggah lewat alur terpisah
                'logo_path' => 'storage/logo/tidak-boleh-tersimpan.png',
            ],
            'letterhead' => [
                'cc_list' => [
                    'Kepala Dinas Pendidikan Kab. Trenggalek',
                    'Sdr. Kepala Satuan Kerja {satker}',
                    'Arsip',
                ],
            ],
            'nigy' => [
                'format' => '{tahun_masuk}{kode_satker}{urut}',
                'padding' => 3,
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Setting::get('foundation.chairman_name'))->toBe('Hj. Zumrotun Nasihah')
        ->and(Setting::get('foundation.default_issued_place'))->toBe('Trenggalek')
        ->and(Setting::get('foundation.address'))->toBe('Gondang, Tugu, Trenggalek, Jawa Timur')
        ->and(Setting::get('letterhead.cc_list'))->toBe([
            'Kepala Dinas Pendidikan Kab. Trenggalek',
            'Sdr. Kepala Satuan Kerja {satker}',
            'Arsip',
        ]);

    // wildcard `letterhead.cc_list.*` hanya aturan validasi anak, bukan setting.
    expect(Setting::where('key', 'letterhead.cc_list.*')->exists())->toBeFalse()
        // field di luar skema tidak ikut tertulis
        ->and(Setting::get('foundation.logo_path'))->toBeNull();
});

it('menerima larik tembusan kosong tanpa error validasi', function () {
    Setting::set('letterhead.cc_list', ['Arsip'], 'letterhead');

    $admin = User::factory()->foundationAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            'foundation' => [
                'name' => 'Yayasan Pondok Pesantren Qomarul Hidayah',
                'chairman_name' => 'Hj. Zumrotun Nasihah',
                'chairman_position' => 'Ketua Yayasan',
                'default_issued_place' => 'Gondang',
            ],
            'letterhead' => ['cc_list' => []],
            'nigy' => ['format' => '{tahun_masuk}{kode_satker}{urut}', 'padding' => 3],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Setting::get('letterhead.cc_list'))->toBe([]);
});
