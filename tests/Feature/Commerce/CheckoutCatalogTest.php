<?php

use App\Services\AnimatorshoCatalogService;
use Database\Seeders\AnimatorshoCourseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(AnimatorshoCourseSeeder::class);
});

test('checkout loads seeded full and chapter packages from database', function () {
    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/index')
            ->has('fullPackage', fn (Assert $package) => $package
                ->where('slug', 'full')
                ->where('title', 'دوره جامع انیماتورشو')
                ->where('priceToman', 5_500_000)
                ->where('chapterNumber', null)
            )
            ->has('chapterPackages', 4)
            ->where('chapterPackages.0.slug', 'chapter-1')
            ->where('chapterPackages.0.priceToman', 1_500_000)
            ->where('chapterPackages.1.priceToman', 1_750_000)
            ->where('chapterPackages.3.slug', 'chapter-4')
            ->where('chapterPackages.3.priceToman', 1_500_000)
        );
});

test('checkout confirm full cash shows database price', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'cash',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('summary.variant', 'full-cash')
            ->where('summary.title', 'دوره جامع انیماتورشو')
            ->where('summary.priceLine', '۵.۵۰۰.۰۰۰ تومان')
            ->where('summary.primaryCtaLabel', 'انتخاب روش پرداخت')
            ->where('summary.primaryCtaHref', '#payment-methods')
            ->where('showChapterSelector', false)
            ->where('showInstallmentForm', false)
        );
});

test('checkout confirm full installment works without cash price line', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'installment',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('summary.variant', 'full-installment')
            ->where('summary.title', 'دوره جامع انیماتورشو')
            ->where('summary.priceLine', null)
            ->where('summary.primaryCtaLabel', 'تکمیل درخواست اقساطی')
            ->where('summary.primaryCtaHref', '#installment-form')
            ->where('showInstallmentForm', true)
        );
});

test('checkout confirm chapter with slug shows chapter package from database', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'chapter',
        'chapter' => 'chapter-2',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('summary.variant', 'chapter')
            ->where('summary.title', 'فصل دوم: طراحی کاراکتر')
            ->where('summary.priceLine', '۱.۷۵۰.۰۰۰ تومان')
            ->where('summary.primaryCtaLabel', 'انتخاب روش پرداخت')
            ->where('summary.primaryCtaHref', '#payment-methods')
            ->where('showChapterSelector', false)
        );
});

test('checkout confirm chapter without slug shows chapter selector from database', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'chapter',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('checkout/confirm')
            ->where('summary.variant', 'chapter')
            ->where('showChapterSelector', true)
            ->has('chapterPackages', 4)
            ->where('summary.priceLine', null)
        );
});

test('invalid checkout confirm package redirects to checkout', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'invalid',
    ]))->assertRedirect(route('checkout'));
});

test('invalid checkout confirm chapter slug redirects to checkout', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'chapter',
        'chapter' => 'not-a-chapter',
    ]))->assertRedirect(route('checkout'));
});

test('invalid checkout confirm full payment redirects to checkout', function () {
    $this->get(route('checkout.confirm', [
        'package' => 'full',
        'payment' => 'invalid',
    ]))->assertRedirect(route('checkout'));
});

test('catalog service returns published animatorsho packages', function () {
    $catalog = app(AnimatorshoCatalogService::class)->catalogForInertia();

    expect($catalog)->not->toBeNull()
        ->and($catalog['fullPackage']['slug'])->toBe('full')
        ->and($catalog['chapterPackages'])->toHaveCount(4);
});
