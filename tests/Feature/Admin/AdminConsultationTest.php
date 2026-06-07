<?php

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guest cannot access admin consultations', function () {
    $this->get(route('admin.consultations.index'))->assertRedirect(route('login'));
});

test('non-admin cannot access admin consultations', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.consultations.index'))->assertForbidden();
});

test('admin can view consultation list', function () {
    $admin = User::factory()->admin()->create();

    ConsultationRequest::factory()->create(['name' => 'درخواست اول']);
    ConsultationRequest::factory()->create(['name' => 'درخواست دوم']);

    $this->actingAs($admin)->get(route('admin.consultations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/consultations/index')
            ->has('consultations.data', 2));
});

test('admin can update consultation status', function () {
    $admin = User::factory()->admin()->create();
    $request = ConsultationRequest::factory()->create([
        'status' => ConsultationRequestStatus::New,
    ]);

    $this->actingAs($admin)->patch(route('admin.consultations.update', $request), [
        'status' => ConsultationRequestStatus::Contacted->value,
        'admin_note' => null,
    ])->assertRedirect();

    expect($request->fresh()->status)->toBe(ConsultationRequestStatus::Contacted);
});

test('admin can update consultation admin note', function () {
    $admin = User::factory()->admin()->create();
    $request = ConsultationRequest::factory()->create();

    $this->actingAs($admin)->patch(route('admin.consultations.update', $request), [
        'status' => ConsultationRequestStatus::FollowUp->value,
        'admin_note' => 'فردا تماس بگیر.',
    ])->assertRedirect();

    $request->refresh();

    expect($request->status)->toBe(ConsultationRequestStatus::FollowUp)
        ->and($request->admin_note)->toBe('فردا تماس بگیر.');
});

test('admin consultation list filters by status', function () {
    $admin = User::factory()->admin()->create();

    ConsultationRequest::factory()->withStatus(ConsultationRequestStatus::New)->create(['name' => 'جدید']);
    ConsultationRequest::factory()->withStatus(ConsultationRequestStatus::Contacted)->create(['name' => 'تماس']);

    $this->actingAs($admin)->get(route('admin.consultations.index', ['status' => 'new']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('consultations.data', 1)
            ->where('consultations.data.0.name', 'جدید')
            ->where('filters.status', 'new'));
});

test('admin consultation list search by name works', function () {
    $admin = User::factory()->admin()->create();

    ConsultationRequest::factory()->create(['name' => 'سارا احمدی', 'mobile' => '09121111111']);
    ConsultationRequest::factory()->create(['name' => 'رضا کریمی', 'mobile' => '09122222222']);

    $this->actingAs($admin)->get(route('admin.consultations.index', ['q' => 'سارا']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('consultations.data', 1)
            ->where('consultations.data.0.name', 'سارا احمدی'));
});

test('admin consultation list search by mobile works', function () {
    $admin = User::factory()->admin()->create();

    ConsultationRequest::factory()->create(['name' => 'سارا احمدی', 'mobile' => '09121111111']);
    ConsultationRequest::factory()->create(['name' => 'رضا کریمی', 'mobile' => '09122222222']);

    $this->actingAs($admin)->get(route('admin.consultations.index', ['q' => '0912222']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('consultations.data', 1)
            ->where('consultations.data.0.mobile', '09122222222'));
});

test('non-admin cannot update consultation', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $request = ConsultationRequest::factory()->create();

    $this->actingAs($user)->patch(route('admin.consultations.update', $request), [
        'status' => ConsultationRequestStatus::Contacted->value,
    ])->assertForbidden();
});
