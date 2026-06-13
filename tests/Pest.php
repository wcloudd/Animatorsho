<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit/Security');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @return array{customer_name: string, customer_mobile: string}
 */
function validCheckoutCustomer(): array
{
    return [
        'customer_name' => 'علی رضایی',
        'customer_mobile' => '09121234567',
    ];
}

/**
 * @return array{customer_name: string}
 */
function validCheckoutCustomerNameOnly(): array
{
    return [
        'customer_name' => 'علی رضایی',
    ];
}

/**
 * @template T
 *
 * @param  callable(): T  $callback
 * @return T
 */
function withoutDefaultStudentPanelMediaFiles(callable $callback): mixed
{
    $directory = public_path('media/student-panel');
    $filenames = [
        'onboarding-banner.png',
        'exercises-header.png',
        'mentor-header.png',
        'resources-header.png',
        'medals-header.png',
        'updates-header.png',
        'start-guide.mp4',
        'start-guide-poster.png',
        'start-guide.pdf',
    ];

    $restored = [];

    foreach ($filenames as $filename) {
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            continue;
        }

        $backupPath = $path.'.pest-backup';

        if (is_file($backupPath)) {
            @unlink($backupPath);
        }

        rename($path, $backupPath);
        $restored[$path] = $backupPath;
    }

    try {
        return $callback();
    } finally {
        foreach ($restored as $originalPath => $backupPath) {
            if (is_file($backupPath)) {
                rename($backupPath, $originalPath);
            }
        }
    }
}

function prepareAuthPageTests(): void
{
    config(['inertia.ssr.enabled' => false]);
    test()->withoutVite();
}
