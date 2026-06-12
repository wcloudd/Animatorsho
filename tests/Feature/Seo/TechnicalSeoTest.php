<?php

use App\Models\CoursePackage;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.url' => 'https://animatorsho.test']);
});

test('robots.txt returns expected disallow rules and sitemap line', function () {
    $response = $this->get(route('seo.robots'));

    $response->assertOk();
    $content = $response->getContent() ?: '';

    expect($content)->toContain('User-agent: *')
        ->and($content)->toContain('Disallow: /admin')
        ->and($content)->toContain('Disallow: /profile')
        ->and($content)->toContain('Disallow: /course')
        ->and($content)->toContain('Disallow: /support')
        ->and($content)->toContain('Sitemap: https://animatorsho.test/sitemap.xml')
        ->and($content)->not->toContain('localhost')
        ->and($content)->not->toContain('127.0.0.1');
});

test('sitemap includes public urls only', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $response = $this->get(route('seo.sitemap'));

    $response->assertOk();
    $content = $response->getContent() ?: '';

    expect($content)->toContain('https://animatorsho.test/')
        ->and($content)->toContain('https://animatorsho.test/consultation')
        ->and($content)->toContain('https://animatorsho.test/checkout')
        ->and($content)->not->toContain('/admin')
        ->and($content)->not->toContain('/profile')
        ->and($content)->not->toContain('/support')
        ->and($content)->not->toContain('/checkout/result')
        ->and($content)->not->toContain('/login')
        ->and($content)->not->toContain('localhost')
        ->and($content)->not->toContain('127.0.0.1');
});

test('home page shares seo structured data props', function () {
    $this->seed(AnimatorshoCourseSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/index')
            ->has('seo.organization')
            ->has('seo.ogImage')
            ->where('seo.organization.name', 'انیماتورشو')
            ->etc());
});

test('admin pages include noindex response header', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('profile pages include noindex response header', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('course home includes noindex response header', function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);

    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('checkout result includes noindex response header', function () {
    $this->get(route('checkout.result', ['status' => 'success']))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('auth mobile login page includes noindex response header', function () {
    $this->get(route('auth.mobile.create'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('public login page does not include noindex response header', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    expect($response->headers->get('X-Robots-Tag'))->toBeNull();
});

test('consultation page does not include noindex response header', function () {
    $response = $this->get(route('consultation'));

    $response->assertOk();
    expect($response->headers->get('X-Robots-Tag'))->toBeNull();
});
