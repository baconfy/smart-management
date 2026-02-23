<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class IndexController extends Controller
{
    /**
     * Display the user's profile settings page.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('profile/index', [
            'mustVerifyEmail' => Features::enabled(Features::emailVerification()),
            'status' => $request->session()->get('status'),
        ]);
    }
}
