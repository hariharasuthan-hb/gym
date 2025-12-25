@extends('admin.layouts.app')

@section('page-title', 'My Profile')

@section('content')
<div class="max-w-4xl mx-auto">
    @if(session('status') === 'profile-updated')
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Profile updated successfully.
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white">Profile Information</h2>
            <p class="text-sm text-blue-100 mt-1">Update your account's profile information and email address.</p>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
                @csrf
                @method('patch')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-1">
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                            Full Name
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                value="{{ old('name', $user->name) }}"
                                required
                                placeholder="Enter your full name"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                                       transition duration-200 ease-in-out
                                       @error('name') border-red-500 focus:ring-red-500 @enderror
                                       placeholder-gray-400 text-gray-900 bg-white"
                            >
                            @error('name')
                                <svg class="absolute right-3 top-3 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            @enderror
                        </div>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Email Address
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                value="{{ old('email', $user->email) }}"
                                required
                                placeholder="your.email@example.com"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                                       focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                                       transition duration-200 ease-in-out
                                       @error('email') border-red-500 focus:ring-red-500 @enderror
                                       placeholder-gray-400 text-gray-900 bg-white"
                            >
                            @error('email')
                                <svg class="absolute right-3 top-3 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            @enderror
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            Your email address is unverified.
                            <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">
                                @csrf
                                <button type="submit" class="underline text-sm text-yellow-600 hover:text-yellow-900">
                                    Click here to re-send the verification email.
                                </button>
                            </form>
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your email address.
                            </p>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg shadow-blue-500/30 hover:shadow-xl font-semibold">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

