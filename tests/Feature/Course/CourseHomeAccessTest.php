<?php

use App\Enums\OrderPaymentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SpotPlayerLicenseStatus;
use App\Models\CoursePackage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('guest cannot access course home and is redirected to login', function () {
    $this->get(route('course.home'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('url.intended', route('course.home'));
});

test('authenticated user without access is redirected to profile with flash message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'))
        ->assertSessionHas(
            'status',
            'دسترسی فعالی برای دوره ندارید. وضعیت ثبت‌نام و لایسنس را در پروفایل بررسی کنید.',
        );
});

test('user with pending payment cannot access course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->create([
            'status' => OrderStatus::Pending,
            'payment_type' => OrderPaymentType::Cash,
        ]);

    Payment::factory()->forOrder($order)->create([
        'method' => PaymentMethod::Zarinpal,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('user with paid license pending cannot access course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    $order = Order::factory()
        ->for($user)
        ->forPackage($package)
        ->paid()
        ->create();

    Payment::factory()->forOrder($order)->paid()->create();

    SpotPlayerLicense::factory()
        ->forOrder($order)
        ->create([
            'user_id' => $user->id,
            'status' => SpotPlayerLicenseStatus::Pending,
            'license_key' => null,
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('user with revoked license cannot access course home', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->revoked()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-REVOKED-SECRET',
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertRedirect(route('profile'));
});

test('user with active full package license can access course home', function () {
    $user = User::factory()->create(['name' => 'کاربر دوره']);
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-FULL-ACCESS',
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/course-home')
            ->where('welcome.displayName', 'کاربر دوره')
            ->where('welcome.firstName', 'کاربر')
            ->where('progress.level', 1)
            ->where('progress.totalXp', 0)
            ->where('onboarding.heading', 'از اینجا شروع کن')
            ->where('onboarding.imageAlt', 'مسیر شروع انیماتورشو')
            ->where('onboarding.videoGuideLabel', 'ویدئو راهنما')
            ->where('onboarding.pdfGuideLabel', 'دانلود راهنما')
            ->where('onboarding.videoUrl', null)
            ->where('onboarding.pdfUrl', null)
            ->where('onboarding.videoTitle', 'ویدئو راهنمای پنل هنرجو')
            ->where(
                'onboarding.pdfDownloadName',
                'rahnamaye-shoroo-animatorsho.pdf',
            )
            ->missing('onboarding.cta')
            ->where('preview.notificationsUnread', 0)
            ->has('preview.updates', 2)
            ->has('preview.resources', 3)
            ->has('preview.medals.locked', 6)
            ->has('preview.sectionVisuals.exercises')
            ->where(
                'preview.sectionVisuals.exercises.placeholderTitle',
                'تصویر تمرین',
            )
            ->where(
                'preview.sectionVisuals.updates.placeholderTitle',
                'تصویر آپدیت‌ها',
            )
            ->missing('chapters')
            ->missing('spotPlayerLicenses')
        );
});

test('user with active chapter license can access course home', function () {
    $user = User::factory()->create(['name' => 'هنرجوی فصل']);
    $chapterPackage = CoursePackage::query()->where('slug', 'chapter-1')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $chapterPackage->id,
            'license_key' => 'SPOT-CHAPTER-1',
        ]);

    $this->actingAs($user)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('animatorsho/course-home')
            ->where('welcome.displayName', 'هنرجوی فصل')
            ->where('welcome.firstName', 'هنرجوی')
            ->has('progress')
            ->has('onboarding')
            ->has('preview')
            ->missing('chapters')
            ->missing('spotPlayerLicenses')
        );
});

test('course home only exposes the authenticated users welcome data', function () {
    $owner = User::factory()->create(['name' => 'کاربر اصلی']);
    $otherUser = User::factory()->create(['name' => 'کاربر دیگر']);
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $owner->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-OWNER-ONLY',
        ]);

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $otherUser->id,
            'course_package_id' => $package->id,
            'license_key' => 'SPOT-OTHER-USER',
        ]);

    $this->actingAs($owner)
        ->get(route('course.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('welcome.displayName', 'کاربر اصلی')
            ->where('welcome.firstName', 'کاربر')
            ->missing('spotPlayerLicenses')
        );
});

test('shared nav animatorsho href points to home for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nav.animatorshoHref', route('home'))
        );
});

test('shared nav animatorsho href points to home for authenticated users without access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nav.animatorshoHref', route('home'))
        );
});

test('shared nav animatorsho href points to course home for users with active access', function () {
    $user = User::factory()->create();
    $package = CoursePackage::query()->where('slug', 'full')->firstOrFail();

    SpotPlayerLicense::factory()
        ->active()
        ->create([
            'user_id' => $user->id,
            'course_package_id' => $package->id,
        ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nav.animatorshoHref', route('course.home'))
        );
});

test('course home page includes noindex response header', function () {
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
