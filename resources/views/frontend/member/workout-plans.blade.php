@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Workout Plans</h1>
            <p class="mt-2 text-gray-600">View and manage your personalized workout plans.</p>
        </div>

        {{-- Workout Plans Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Sample Workout Plan Card 1 --}}
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                    <h3 class="text-xl font-bold text-white">Strength Training</h3>
                    <p class="text-blue-100 text-sm mt-1">Full Body Workout</p>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Duration</span>
                        <span class="text-sm font-semibold text-gray-900">45 min</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Difficulty</span>
                        <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded">Intermediate</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Exercises</span>
                        <span class="text-sm font-semibold text-gray-900">8 exercises</span>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <a href="#" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            View Details
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sample Workout Plan Card 2 --}}
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-6">
                    <h3 class="text-xl font-bold text-white">Cardio Blast</h3>
                    <p class="text-green-100 text-sm mt-1">High Intensity</p>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Duration</span>
                        <span class="text-sm font-semibold text-gray-900">30 min</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Difficulty</span>
                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">Advanced</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Exercises</span>
                        <span class="text-sm font-semibold text-gray-900">6 exercises</span>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <a href="#" class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                            View Details
                        </a>
                    </div>
                </div>
            </div>

            {{-- Sample Workout Plan Card 3 --}}
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6">
                    <h3 class="text-xl font-bold text-white">Flexibility & Stretch</h3>
                    <p class="text-purple-100 text-sm mt-1">Yoga & Mobility</p>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Duration</span>
                        <span class="text-sm font-semibold text-gray-900">25 min</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Difficulty</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Beginner</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm font-medium text-gray-500">Exercises</span>
                        <span class="text-sm font-semibold text-gray-900">10 exercises</span>
                    </div>
                    <div class="pt-4 border-t border-gray-200">
                        <a href="#" class="block w-full text-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Empty State (if no plans) --}}
        <div class="hidden bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No workout plans yet</h3>
            <p class="mt-2 text-sm text-gray-500">Your assigned workout plans will appear here.</p>
        </div>
    </div>
</div>
@endsection

