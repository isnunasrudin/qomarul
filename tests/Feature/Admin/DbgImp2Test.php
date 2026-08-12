<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('t1', function () {
    $admin = User::factory()->foundationAdmin()->create();
    $target = User::factory()->create(['role' => UserRole::Employee]);

    $res = $this->actingAs($admin)->post("/admin/users/{$target->id}/impersonate");
    echo 'STATUS1: '.$res->status().PHP_EOL;

    if ($res->status() === 403) {
        echo 'BODY: '.substr($res->getContent(), 0, 200).PHP_EOL;

        return;
    }

    $this->assertAuthenticatedAs($target);
    echo 'session: '.var_export(session('impersonator_id'), true).PHP_EOL;

    $g = $this->get(route('portal.home'));
    echo 'STATUS2: '.$g->status().PHP_EOL;
    if ($g->status() === 403) {
        echo 'BODY2: '.substr($g->getContent(), 0, 200).PHP_EOL;

        return;
    }
    $g->assertInertia(fn (Assert $page) => $page
        ->where('impersonation.active', true)
        ->where('impersonation.impersonator.id', $admin->id));
    echo 'OK'.PHP_EOL;
});
