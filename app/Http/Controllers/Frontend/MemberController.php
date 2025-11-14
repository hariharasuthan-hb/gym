<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Get member role ID
        $memberRole = Role::where('name', 'member')->first();
        
        if (!$memberRole) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Member role not found. Please contact administrator.']);
        }

        // Add role ID to validated data
        $validated['role'] = $memberRole->id;

        // Create user with member role using repository
        $this->userRepository->createWithRole($validated);

        return redirect()->route('login')
            ->with('success', 'Registration successful! Please login.');
    }

    /**
     * Show member dashboard.
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        // Check if user has an active subscription
        $activeSubscription = $user->subscriptions()
            ->with('subscriptionPlan')
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();
        
        // Get active subscription plans if user has no active subscription
        $subscriptionPlans = null;
        if (!$activeSubscription) {
            $subscriptionPlans = SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get();
        }
        
        return view('frontend.member.dashboard', compact('activeSubscription', 'subscriptionPlans'));
    }

    /**
     * Show member profile.
     */
    public function profile(): View
    {
        $user = auth()->user();
        return view('frontend.member.profile', compact('user'));
    }

    /**
     * Update member profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);

        return redirect()->route('member.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update member password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('member.profile')
            ->with('success', 'Password updated successfully.');
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

