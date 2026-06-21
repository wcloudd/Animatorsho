<?php

use App\Models\StudentMedalAward;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('guest cannot access admin student medals page', function () {
    $this->get(route('admin.student-medals.index'))
        ->assertRedirect(route('login'));
});

test('non-admin cannot access admin student medals page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.student-medals.index'))
        ->assertForbidden();
});

test('admin can access medal assignment page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.student-medals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/student-medals/index')
            ->has('medals', 6)
            ->has('recentAwards'));
});

test('admin can award a predefined medal to a student', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.student-medals.store'), [
            'user_id' => $student->id,
            'medal_key' => 'first_approved_exercise',
            'note' => 'خوب بود',
        ])
        ->assertRedirect(route('admin.student-medals.index'));

    expect(StudentMedalAward::query()->where('user_id', $student->id)->where('medal_key', 'first_approved_exercise')->exists())
        ->toBeTrue();
});

test('duplicate medal award for the same student is rejected', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    StudentMedalAward::create([
        'user_id' => $student->id,
        'medal_key' => 'first_approved_exercise',
        'awarded_by' => $admin->id,
        'awarded_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.student-medals.store'), [
            'user_id' => $student->id,
            'medal_key' => 'first_approved_exercise',
        ])
        ->assertSessionHasErrors(['medal_key']);

    expect(StudentMedalAward::query()->where('user_id', $student->id)->count())->toBe(1);
});

test('admin can revoke a medal', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $award = StudentMedalAward::create([
        'user_id' => $student->id,
        'medal_key' => 'first_story_written',
        'awarded_by' => $admin->id,
        'awarded_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.student-medals.destroy', $award))
        ->assertRedirect(route('admin.student-medals.index'));

    expect(StudentMedalAward::query()->find($award->id))->toBeNull();
});

test('invalid medal_key is rejected', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.student-medals.store'), [
            'user_id' => $student->id,
            'medal_key' => 'invalid_medal_that_does_not_exist',
        ])
        ->assertSessionHasErrors(['medal_key']);

    expect(StudentMedalAward::query()->count())->toBe(0);
});

test('student cannot award a medal', function () {
    $student = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create();

    $this->actingAs($student)
        ->post(route('admin.student-medals.store'), [
            'user_id' => $target->id,
            'medal_key' => 'first_story_written',
        ])
        ->assertForbidden();
});

test('admin page shows recently awarded medals', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->create(['name' => 'هنرجوی تست']);

    StudentMedalAward::create([
        'user_id' => $student->id,
        'medal_key' => 'first_storyboard',
        'awarded_by' => $admin->id,
        'awarded_at' => now(),
        'note' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.student-medals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentAwards', 1)
            ->where('recentAwards.0.studentName', 'هنرجوی تست')
            ->where('recentAwards.0.medalKey', 'first_storyboard'));
});
