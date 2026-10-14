<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Serve the landing page or route the visitor to their next destination.
     *
     * When the home page is disabled, guests go to the sign-in screen and
     * authenticated users to the dashboard, so the route never returns a 404.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (Setting::homeIsEnabled()) {
            return view('welcome');
        }

        if ($request->user() === null) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard');
    }
}
