@extends('admin.layouts.app')

@section('page-title', 'Create Diet Plan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-semibold text-gray-900">Create New Diet Plan</h1>
        <a href="{{ route('admin.diet-plans.index') }}" class="btn btn-secondary">
            Back to Plans
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.diet-plans.store') }}" method="POST">
            @csrf
            @include('admin.diet-plans._form', ['dietPlan' => null, 'isEdit' => false])
            
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('admin.diet-plans.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit"
                        class="btn btn-primary {{ auth()->user()->hasRole('admin') && collect($trainers ?? [])->isEmpty() ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ auth()->user()->hasRole('admin') && collect($trainers ?? [])->isEmpty() ? 'disabled' : '' }}>
                    Create Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('meals-container');
    const addButton = document.getElementById('add-meal');
    const form = document.querySelector('form');
    const mealPlanJsonInput = document.getElementById('meal_plan_json');
    
    // Add meal field
    addButton.addEventListener('click', function() {
        const mealItem = document.createElement('div');
        mealItem.className = 'meal-item flex items-center gap-2';
        mealItem.innerHTML = `
            <input type="text" 
                   name="meals[]" 
                   value=""
                   placeholder="Enter meal (e.g., Breakfast: Oatmeal, Lunch: Grilled Chicken)"
                   class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                          focus:ring-2 focus:ring-purple-500 focus:border-purple-500 
                          transition duration-200 ease-in-out
                          placeholder-gray-400 text-gray-900 bg-white">
            <button type="button" 
                    class="remove-meal text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                    title="Remove meal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        container.appendChild(mealItem);
    });
    
    // Remove meal field
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-meal')) {
            const mealItem = e.target.closest('.meal-item');
            mealItem.remove();
            updateMealPlanJson();
        }
    });
    
    // Convert meals array to JSON before form submission
    function updateMealPlanJson() {
        const mealInputs = container.querySelectorAll('input[name="meals[]"]');
        const meals = Array.from(mealInputs)
            .map(input => input.value.trim())
            .filter(value => value !== '');
        mealPlanJsonInput.value = JSON.stringify(meals);
    }
    
    // Update JSON on input change
    container.addEventListener('input', function(e) {
        if (e.target.name === 'meals[]') {
            updateMealPlanJson();
        }
    });
    
    // Convert to JSON before form submission
    form.addEventListener('submit', function(e) {
        updateMealPlanJson();
    });
    
    // Initial JSON update
    updateMealPlanJson();
});
</script>
@endpush

