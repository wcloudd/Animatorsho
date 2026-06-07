<?php

use App\Models\ConsultationRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('guest can submit valid consultation request', function () {
    $this->post(route('consultation.store'), [
        'full_name' => 'علی رضایی',
        'mobile' => '09123456789',
        'note' => 'می‌خوام بدونم دوره برای من مناسبه یا نه.',
        'level' => 'beginner',
        'interest' => 'full-course',
    ])
        ->assertRedirect(route('consultation'));

    $request = ConsultationRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->name)->toBe('علی رضایی')
        ->and($request->mobile)->toBe('09123456789')
        ->and($request->note)->toBe('می‌خوام بدونم دوره برای من مناسبه یا نه.')
        ->and($request->level)->toBe('beginner')
        ->and($request->interest)->toBe('full-course')
        ->and($request->user_id)->toBeNull();
});

test('authenticated user can submit valid consultation request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('consultation.store'), [
        'full_name' => 'کاربر تست',
        'mobile' => '09121112222',
    ])
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->first()?->user_id)->toBe($user->id);
});

test('invalid mobile fails validation', function () {
    $this->from(route('consultation'))
        ->post(route('consultation.store'), [
            'full_name' => 'علی رضایی',
            'mobile' => '12345',
        ])
        ->assertRedirect(route('consultation'))
        ->assertSessionHasErrors('mobile');

    expect(ConsultationRequest::query()->count())->toBe(0);
});

test('mobile is normalized on submit', function () {
    $this->post(route('consultation.store'), [
        'full_name' => 'علی رضایی',
        'mobile' => '+98 912 345 6789',
    ])->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->first()?->mobile)->toBe('09123456789');
});

test('consultation submit redirects back to consultation page on success', function () {
    $this->post(route('consultation.store'), [
        'full_name' => 'علی رضایی',
        'mobile' => '09123456789',
    ])
        ->assertRedirect(route('consultation'));

    expect(ConsultationRequest::query()->count())->toBe(1);
});

test('consultation page is public', function () {
    $this->get(route('consultation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('consultation/index'));
});

test('consultation submit is throttled', function () {
    $payload = [
        'full_name' => 'علی رضایی',
        'mobile' => '09129998877',
    ];

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('consultation.store'), $payload)->assertRedirect();
    }

    $this->post(route('consultation.store'), $payload)->assertStatus(429);
});
