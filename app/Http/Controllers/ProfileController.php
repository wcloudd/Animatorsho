<?php

namespace App\Http\Controllers;

use App\Services\ProfileDashboardService;
use App\Support\AvatarPresetRegistry;
use App\Support\IranianMobile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileDashboardService $profileDashboard,
    ) {}

    public function index(Request $request): Response
    {
        $dashboard = $this->profileDashboard->forUser($request->user());

        return Inertia::render('profile/index', $dashboard);
    }

    public function settings(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('profile/settings', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatarPreset' => $user->avatar_preset,
                'maskedMobile' => IranianMobile::mask($user->mobile),
                'hasEmail' => $user->hasEmail(),
                'hasPassword' => $user->hasPassword(),
            ],
            'avatarPresets' => AvatarPresetRegistry::forFrontend(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }
}
