<?php

use App\Models\Certificate;
use App\Models\User;
use App\Services\Signing\CertificateGenerator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->admin = User::factory()->foundationAdmin()->create();
});

it('generates a valid p12 from the form fields', function () {
    $this->actingAs($this->admin)
        ->post('/admin/certificates/generate', [
            'name' => 'yayasan-2027',
            'common_name' => 'Yayasan Pondok Pesantren Qomarul Hidayah',
            'organization' => 'YPP Qomarul Hidayah',
            'organizational_unit' => 'Bidang Personalia & SDM',
            'country' => 'ID',
            'state' => 'Jawa Timur',
            'locality' => 'Trenggalek',
            'email' => 'admin@qomarulhidayah.sch.id',
            'days' => 3650,
            'key_bits' => 2048,
            'digest' => 'sha256',
            'password' => 'rahasia123',
        ])
        ->assertSessionHasNoErrors();

    $certificate = Certificate::where('name', 'yayasan-2027')->firstOrFail();

    expect($certificate->is_active)->toBeTrue();
    expect($certificate->subject)->toBe('Yayasan Pondok Pesantren Qomarul Hidayah');
    expect(Crypt::decryptString($certificate->password_encrypted))->toBe('rahasia123');

    Storage::disk('private')->assertExists($certificate->p12_path);

    // .p12 benar-benar valid & bisa dibaca
    $certs = [];
    expect(openssl_pkcs12_read(Storage::disk('private')->get($certificate->p12_path), $certs, 'rahasia123'))->toBeTrue();

    $parsed = openssl_x509_parse($certs['cert']);
    expect($parsed['subject']['O'])->toBe('YPP Qomarul Hidayah');
    expect($parsed['subject']['OU'])->toBe('Bidang Personalia & SDM');
    expect($parsed['subject']['C'])->toBe('ID');
    expect($parsed['subject']['emailAddress'] ?? null)->toBe('admin@qomarulhidayah.sch.id');
});

it('rotates: generating a new certificate deactivates the old one', function () {
    $this->actingAs($this->admin)->post('/admin/certificates/generate', [
        'name' => 'satu', 'common_name' => 'Satu', 'password' => 'rahasia123', 'key_bits' => 2048,
    ]);

    $this->actingAs($this->admin)->post('/admin/certificates/generate', [
        'name' => 'dua', 'common_name' => 'Dua', 'password' => 'rahasia123', 'key_bits' => 2048,
    ]);

    expect(Certificate::where('name', 'satu')->first()->is_active)->toBeFalse();
    expect(Certificate::where('name', 'dua')->first()->is_active)->toBeTrue();
});

it('validates required certificate fields', function () {
    $this->actingAs($this->admin)
        ->post('/admin/certificates/generate', [
            'name' => '',
            'common_name' => '',
            'password' => '123',
        ])
        ->assertSessionHasErrors(['name', 'common_name', 'password']);
});

it('serves the full certificate detail', function () {
    $result = app(CertificateGenerator::class)->generate([
        'common_name' => 'Detail Test',
        'password' => 'rahasia123',
        'key_bits' => 2048,
    ]);

    $certificate = Certificate::create([
        'name' => 'detail-test',
        'p12_path' => 'certificates/detail-test.p12',
        'password_encrypted' => Crypt::encryptString('rahasia123'),
        'subject' => $result['metadata']['subject'],
        'issuer' => $result['metadata']['issuer'],
        'serial' => $result['metadata']['serial'],
        'valid_from' => $result['metadata']['valid_from'],
        'valid_until' => $result['metadata']['valid_until'],
        'fingerprint' => $result['metadata']['fingerprint'],
        'is_active' => true,
    ]);

    Storage::disk('private')->put('certificates/detail-test.p12', $result['p12']);

    $this->actingAs($this->admin)
        ->getJson("/admin/certificates/{$certificate->id}/detail")
        ->assertOk()
        ->assertJson([
            'name' => 'detail-test',
            'is_active' => true,
            'subject' => ['CN' => 'Detail Test'],
        ])
        ->assertJsonStructure(['fingerprint', 'serial', 'valid_from', 'valid_until', 'pem_cert', 'signature_algorithm']);
});
