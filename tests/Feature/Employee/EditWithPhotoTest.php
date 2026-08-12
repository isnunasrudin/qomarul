<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\UploadedFile;

it('updates an employee with a photo via multipart', function () {
    $unit = WorkUnit::factory()->create();
    $employee = Employee::factory()->create(['work_unit_id' => $unit->id]);
    $admin = User::factory()->foundationAdmin()->create();

    $jpeg = imagecreate(4, 4);
    ob_start();
    imagejpeg($jpeg, null, 90);
    $jpegBytes = ob_get_clean();
    imagedestroy($jpeg);

    $this->actingAs($admin)
        ->put("/admin/employees/{$employee->id}", [
            'name' => 'Nama Diubah',
            'gender' => 'male',
            'work_unit_id' => $unit->id,
            'position_id' => $employee->position_id,
            'employment_status_id' => $employee->employment_status_id,
            'foundation_start_date' => '2020-07-01',
            'phone' => '081111112222',
            'photo' => UploadedFile::fake()->createWithContent('foto.jpg', $jpegBytes),
        ])
        ->assertSessionHasNoErrors();

    expect($employee->fresh()->name)->toBe('Nama Diubah');
    expect($employee->fresh()->phone)->toBe('081111112222');
    expect($employee->fresh()->photo_path)->not->toBeNull();
});
