<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MemberController extends Controller
{
    /**
     * Show member registration form.
     */
    public function register(): View
    {
        return view('frontend.member.register');
    }

    /**
     * Store new member registration.
     */
    public function store(Request $request): RedirectResponse
    {
        // TODO: Implement member registration
        return redirect()->route('login')
            ->with('success', 'Registration successful! Please login.');
    }

    /**
     * Show member dashboard.
     */
    public function dashboard(): View
    {
        return view('frontend.member.dashboard');
    }

    /**
     * Show member profile.
     */
    public function profile(): View
    {
        return view('frontend.member.profile');
    }

    /**
     * Show member subscriptions.
     */
    public function subscriptions(): View
    {
        return view('frontend.member.subscriptions');
    }

    /**
     * Show member activities.
     */
    public function activities(): View
    {
        return view('frontend.member.activities');
    }

    /**
     * Show member workout plans.
     */
    public function workoutPlans(): View
    {
        return view('frontend.member.workout-plans');
    }

    /**
     * Show member diet plans.
     */
    public function dietPlans(): View
    {
        return view('frontend.member.diet-plans');
    }
}

