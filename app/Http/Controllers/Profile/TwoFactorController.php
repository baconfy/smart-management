<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class TwoFactorController extends Controller
{
    /**
     * Display the preferences page with two-factor authentication data.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('profile/preferences', [
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPendingConfirmation' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'requiresConfirmation' => Fortify::confirmsTwoFactorAuthentication(),
        ]);
    }
}
