@extends('admin.layouts.app')

@section('page-title', 'Create Workout Plan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-semibold text-gray-900">Create New Workout Plan</h1>
        <a href="{{ route('admin.workout-plans.index') }}" class="btn btn-secondary">
            Back to Plans
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.workout-plans.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin.workout-plans._form', ['workoutPlan' => null, 'isEdit' => false])
            
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('admin.workout-plans.index') }}" class="btn btn-secondary">
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
    // Get form elements
    const container = document.getElementById('exercises-container');
    const addButton = document.getElementById('add-exercise');
    const form = document.querySelector('form');
    const exercisesJsonInput = document.getElementById('exercises_json');
    const startDateInput = document.getElementById('start_date');
    const durationWeeksInput = document.getElementById('duration_weeks');
    const endDateInput = document.getElementById('end_date');
    
    // Auto-calculate end date based on start date and duration
    function calculateEndDate() {
        if (!startDateInput || !durationWeeksInput || !endDateInput) return;
        
        const startDate = startDateInput.value;
        const durationWeeks = parseInt(durationWeeksInput.value) || 0;
        
        if (startDate && durationWeeks > 0) {
            // Parse the start date string (YYYY-MM-DD format)
            const start = new Date(startDate + 'T00:00:00'); // Add time to avoid timezone issues
            
            // Check if date is valid
            if (isNaN(start.getTime())) {
                console.error('Invalid start date:', startDate);
                return;
            }
            
            // Add weeks (duration * 7 days)
            const end = new Date(start);
            end.setDate(end.getDate() + (durationWeeks * 7) - 1); // -1 to make it inclusive
            
            // Check if end date is valid
            if (isNaN(end.getTime())) {
                console.error('Invalid calculated end date');
                return;
            }
            
            // Format as YYYY-MM-DD with proper padding
            const year = end.getFullYear();
            const month = String(end.getMonth() + 1).padStart(2, '0');
            const day = String(end.getDate()).padStart(2, '0');
            
            // Ensure year is 4 digits
            const yearStr = String(year).padStart(4, '0');
            const endDateFormatted = `${yearStr}-${month}-${day}`;
            
            // Validate the formatted date matches YYYY-MM-DD pattern
            if (/^\d{4}-\d{2}-\d{2}$/.test(endDateFormatted)) {
                endDateInput.value = endDateFormatted;
            } else {
                console.error('Date formatting error:', endDateFormatted);
            }
        } else if (!startDate || !durationWeeks) {
            // Clear end date if start date or duration is missing
            endDateInput.value = '';
        }
    }
    
    // Calculate on start date change
    if (startDateInput) {
        startDateInput.addEventListener('change', calculateEndDate);
        startDateInput.addEventListener('input', calculateEndDate);
    }
    
    // Calculate on duration weeks change
    if (durationWeeksInput) {
        durationWeeksInput.addEventListener('change', calculateEndDate);
        durationWeeksInput.addEventListener('input', calculateEndDate);
    }
    
    // Calculate on page load if values exist
    if (startDateInput && durationWeeksInput && startDateInput.value && durationWeeksInput.value) {
        calculateEndDate();
    }
    
    // Exercises management
    if (addButton && container) {
        // Add exercise field
        addButton.addEventListener('click', function() {
            const exerciseItem = document.createElement('div');
            exerciseItem.className = 'exercise-item flex items-center gap-2';
            exerciseItem.innerHTML = `
                <input type="text" 
                       name="exercises[]" 
                       value=""
                       placeholder="Enter exercise name (e.g., Push-ups, Squats, Bench Press)"
                       class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                              focus:ring-2 focus:ring-green-500 focus:border-green-500 
                              transition duration-200 ease-in-out
                              placeholder-gray-400 text-gray-900 bg-white">
                <button type="button" 
                        class="remove-exercise text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                        title="Remove exercise">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            container.appendChild(exerciseItem);
        });
        
        // Remove exercise field
        container.addEventListener('click', function(e) {
            if (e.target.closest('.remove-exercise')) {
                const exerciseItem = e.target.closest('.exercise-item');
                exerciseItem.remove();
                updateExercisesJson();
            }
        });
    }
    
    // Convert exercises array to JSON before form submission
    function updateExercisesJson() {
        if (!container || !exercisesJsonInput) return;
        
        const exerciseInputs = container.querySelectorAll('input[name="exercises[]"]');
        const exercises = Array.from(exerciseInputs)
            .map(input => input.value.trim())
            .filter(value => value !== '');
        exercisesJsonInput.value = JSON.stringify(exercises);
    }
    
    // Update JSON on input change
    if (container) {
        container.addEventListener('input', function(e) {
            if (e.target.name === 'exercises[]') {
                updateExercisesJson();
            }
        });
    }
    
    // Convert to JSON before form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            updateExercisesJson();
        });
    }
    
    // Initial JSON update
    updateExercisesJson();
});
</script>
<script src="{{ asset('js/video-upload-utils.js') }}"></script>
<script src="{{ asset('js/admin-video-upload.js') }}"></script>
@endpush

