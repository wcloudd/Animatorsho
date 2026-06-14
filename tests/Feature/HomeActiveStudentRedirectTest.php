<?php

use App\Models\CoursePackage;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
});

function createLicensedStudentForHome(): User
{
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
        ]);

    return $user;
}

test('guest visiting home sees landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('animatorsho/index'));
});

test('authenticated user without active access visiting home does not redirect to course', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('animatorsho/index'));
});

test('authenticated user with active access visiting home redirects to course', function () {
    $user = createLicensedStudentForHome();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('course.home'));
});
