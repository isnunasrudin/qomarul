<?php

use App\Enums\UserRole;
use App\Models\User;

it('debug 403', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $target = User::factory()->create(['role' => UserRole::Employee]);
    expect($admin->can('impersonate', $target))->toBeTrue();

    $res = $this->actingAs($admin)->post("/admin/users/{$target->id}/impersonate");
    if ($res->status() === 403) {
        echo '403: '.substr($res->getContent(), 0, 300).PHP_EOL;
    } else {
        echo 'status: '.$res->status().PHP_EOL;
        $res->assertRedirect();
    }
});
