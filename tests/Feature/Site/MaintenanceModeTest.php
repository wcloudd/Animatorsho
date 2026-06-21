<?php

use App\Models\User;
use App\Services\SiteSettingsService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function enableMaintenanceMode(?string $title = null, ?string $message = null): void
{
    $data = ['maintenance_mode_enabled' => true];

    if ($title !== null) {
        $data['maintenance_title'] = $title;
    }

    if ($message !== null) {
        $data['maintenance_message'] = $message;
    }

    app(SiteSettingsService::class)->update($data);
}

test('maintenance mode shows maintenance page for public guests', function () {
    enableMaintenanceMode('در حال بروزرسانی هستیم', 'به‌زودی برمی‌گردیم.');

    $this->get(route('home'))
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('maintenance/index')
            ->where('title', 'در حال بروزرسانی هستیم')
            ->where('message', 'به‌زودی برمی‌گردیم.')
            ->where('auth.user', null));
});

test('maintenance mode blocks checkout for public guests', function () {
    enableMaintenanceMode();

    $this->get(route('checkout'))
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page->component('maintenance/index'));
});

test('logged in admin bypasses maintenance mode', function () {
    enableMaintenanceMode();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('animatorsho/index'));
});

test('guest can reach login during maintenance mode', function () {
    enableMaintenanceMode();

    $this->get(route('login'))->assertOk();
});

test('guest can complete login identifier step during maintenance mode', function () {
    enableMaintenanceMode();

    $admin = User::factory()->admin()->withMobile('09121234567')->create();

    $this->post(route('login.identifier'), [
        'identifier' => $admin->mobile,
    ])
        ->assertRedirect(route('login.password'))
        ->assertSessionHas('mobile_otp.mobile', $admin->mobile);
});

test('guest can reach login password page during maintenance mode', function () {
    enableMaintenanceMode();

    $admin = User::factory()->admin()->withMobile('09121234567')->create();

    $this->withSession(['mobile_otp.mobile' => $admin->mobile])
        ->get(route('login.password'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login-password'));
});

test('admin panel remains accessible during maintenance mode', function () {
    enableMaintenanceMode();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/dashboard'));
});

test('zarinpal callback bypasses maintenance mode', function () {
    enableMaintenanceMode();

    $this->get(route('checkout.zarinpal.callback'))
        ->assertRedirect(route('checkout.result'));
});

test('maintenance page uses fallback copy when custom fields are empty', function () {
    enableMaintenanceMode();

    app(SiteSettingsService::class)->update([
        'maintenance_title' => '',
        'maintenance_message' => '',
    ]);

    $this->get(route('home'))
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->where('title', 'در حال بروزرسانی هستیم')
            ->where('message', fn (string $message) => str_contains($message, 'به‌روزرسانی')));
});

test('logged in non-admin user sees maintenance page', function () {
    enableMaintenanceMode('در حال بروزرسانی هستیم', 'به‌زودی برمی‌گردیم.');

    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('maintenance/index')
            ->where('title', 'در حال بروزرسانی هستیم')
            ->where('message', 'به‌زودی برمی‌گردیم.')
            ->has('auth.user')
            ->where('auth.isAdmin', false));
});

test('logged in non-admin has authenticated session on maintenance page for logout ui', function () {
    enableMaintenanceMode();

    $user = User::factory()->create(['is_admin' => false, 'name' => 'کاربر تست']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertStatus(503)
        ->assertInertia(fn (Assert $page) => $page
            ->component('maintenance/index')
            ->where('auth.user.id', $user->id)
            ->where('auth.user.name', 'کاربر تست')
            ->where('auth.isAdmin', false));
});

test('post logout works during maintenance mode', function () {
    enableMaintenanceMode();

    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('health endpoint bypasses maintenance mode', function () {
    enableMaintenanceMode();

    $this->get('/up')->assertOk();
});
