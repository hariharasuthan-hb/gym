@php
    $workoutPlan = $workoutPlan ?? null;
    $isEdit = $isEdit ?? false;
    $members = $members ?? [];
    $trainers = $trainers ?? collect();
@endphp

@if(auth()->user()->hasRole('admin') && $trainers->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">
                    Action Required: No Trainers Available
                </h3>
                <div class="mt-2 text-sm text-amber-700">
                    <p>You cannot create workout plans without trainers. Please add at least one trainer first.</p>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="space-y-4">
    {{-- Plan Information Section --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl border border-blue-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Plan Information</h3>
        
        <div class="grid grid-cols-2 gap-4">
            @include('admin.components.form-input', [
                'name' => 'plan_name',
                'label' => 'Plan Name',
                'value' => old('plan_name', $workoutPlan->plan_name ?? null),
                'required' => true,
                'placeholder' => 'e.g., Beginner Strength Training',
            ])
            
            @include('admin.components.form-select', [
                'name' => 'member_id',
                'label' => 'Member',
                'options' => $members->pluck('name', 'id')->toArray(),
                'value' => old('member_id', $workoutPlan->member_id ?? null),
                'required' => true,
                'placeholder' => 'Select a member',
            ])
            
            @if(auth()->user()->hasRole('admin'))
                @if($trainers->isNotEmpty())
                    @include('admin.components.form-select', [
                        'name' => 'trainer_id',
                        'label' => 'Trainer',
                        'options' => $trainers->pluck('name', 'id')->toArray(),
                        'value' => old('trainer_id', $workoutPlan->trainer_id ?? null),
                        'required' => true,
                        'placeholder' => 'Select a trainer',
                    ])
                @else
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trainer</label>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-amber-800">
                                        No Trainers Available
                                    </h3>
                                    <div class="mt-2 text-sm text-amber-700">
                                        <p>You need to add trainers before you can assign them to workout plans.</p>
                                        <p class="mt-2">
                                            <a href="{{ route('admin.users.create') }}?role=trainer"
                                               class="font-medium underline text-amber-700 hover:text-amber-600">
                                                Add a Trainer →
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <strong>Note:</strong> You must add at least one trainer before creating workout plans.
                        </p>
                    </div>
                @endif
            @endif
            
            @include('admin.components.form-input', [
                'name' => 'duration_weeks',
                'label' => 'Duration (Weeks)',
                'type' => 'number',
                'value' => old('duration_weeks', $workoutPlan->duration_weeks ?? null),
                'placeholder' => 'e.g., 12',
                'attributes' => ['min' => '1'],
            ])
            
            @include('admin.components.form-select', [
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'paused' => 'Paused',
                    'cancelled' => 'Cancelled',
                ],
                'value' => old('status', $workoutPlan->status ?? 'active'),
                'required' => true,
            ])
            
            @include('admin.components.form-input', [
                'name' => 'start_date',
                'label' => 'Start Date',
                'type' => 'date',
                'value' => old('start_date', format_date_input($workoutPlan->start_date ?? null)),
                'required' => true,
            ])
            
            <div class="md:col-span-1">
                @include('admin.components.form-input', [
                    'name' => 'end_date',
                    'label' => 'End Date',
                    'type' => 'date',
                    'value' => old('end_date', format_date_input($workoutPlan->end_date ?? null)),
                ])
                <p class="text-xs text-gray-500 mt-1">Auto-calculated from start date and duration (can be manually adjusted)</p>
            </div>
        </div>
        
        @include('admin.components.form-textarea', [
            'name' => 'description',
            'label' => 'Description',
            'value' => old('description', $workoutPlan->description ?? null),
            'placeholder' => 'Enter plan description',
            'rows' => 3,
            'colspan' => 2,
        ])
    </div>

    {{-- Exercises Section --}}
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-5 rounded-xl border border-green-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-800">Exercises</h3>
            <button type="button" id="add-exercise" class="text-sm text-green-600 hover:text-green-800 font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Exercise
            </button>
        </div>
        
        <div id="exercises-container" class="space-y-3">
            @php
                // Parse existing exercises
                $existingExercises = [];
                if ($workoutPlan && $workoutPlan->exercises) {
                    if (is_array($workoutPlan->exercises)) {
                        $existingExercises = $workoutPlan->exercises;
                    } else {
                        // Try to decode JSON
                        $decoded = json_decode($workoutPlan->exercises, true);
                        $existingExercises = $decoded ? $decoded : [$workoutPlan->exercises];
                    }
                }
                // If no existing exercises, add one empty field
                if (empty($existingExercises)) {
                    $existingExercises = [''];
                }
            @endphp
            
            @foreach($existingExercises as $index => $exercise)
                <div class="exercise-item flex items-center gap-2">
                    <input type="text" 
                           name="exercises[]" 
                           value="{{ old("exercises.{$index}", is_array($exercise) ? (isset($exercise['name']) ? $exercise['name'] : json_encode($exercise)) : $exercise) }}"
                           placeholder="Enter exercise name (e.g., Push-ups, Squats, Bench Press)"
                           class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                                  focus:ring-2 focus:ring-green-500 focus:border-green-500 
                                  transition duration-200 ease-in-out
                                  @error('exercises.*') border-red-500 focus:ring-red-500 @enderror
                                  placeholder-gray-400 text-gray-900 bg-white">
                    @if($index > 0 || count($existingExercises) > 1)
                        <button type="button" 
                                class="remove-exercise text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                title="Remove exercise">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        
        @error('exercises.*')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        
        <p class="text-xs text-gray-500 mt-3">Add exercises one by one. They will be automatically saved as JSON format.</p>
        
        {{-- Hidden field to store JSON --}}
        <input type="hidden" name="exercises_json" id="exercises_json" value="">
    </div>

    {{-- Demo Video Section --}}
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-5 rounded-xl border border-orange-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            Demo Video
        </h3>
        
        <div class="md:col-span-2">
            @if($workoutPlan && $workoutPlan->demo_video_path)
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Current Demo Video</label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <video controls class="w-full max-w-md rounded-lg" style="max-height: 300px;">
                        <source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($workoutPlan->demo_video_path) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <p class="text-xs text-gray-500 mt-2">{{ basename($workoutPlan->demo_video_path) }}</p>
                </div>
            </div>
            @endif
            
            <label for="demo_video" class="block text-sm font-semibold text-gray-700 mb-2">
                @if($workoutPlan && $workoutPlan->demo_video_path)
                    Replace Demo Video
                @else
                    Upload Demo Video (1 minute)
                @endif
            </label>
            <div class="relative">
                <input 
                    type="file" 
                    name="demo_video" 
                    id="demo_video" 
                    accept="video/mp4,video/webm,video/quicktime"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                           focus:ring-2 focus:ring-orange-500 focus:border-orange-500 
                           transition duration-200 ease-in-out
                           @error('demo_video') border-red-500 focus:ring-red-500 @enderror
                           text-gray-900 bg-white file:mr-4 file:py-2 file:px-4 
                           file:rounded-lg file:border-0 file:text-sm file:font-semibold
                           file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100"
                >
                @error('demo_video')
                    <svg class="absolute right-3 top-3 h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                @enderror
            </div>
            @error('demo_video')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-500 mt-2">
                Max size: 100MB. Formats: MP4, WebM, MOV. Recommended: 1 minute duration.
            </p>
        </div>
    </div>

    {{-- Notes Section --}}
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-5 rounded-xl border border-purple-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Additional Notes</h3>
        
        @include('admin.components.form-textarea', [
            'name' => 'notes',
            'label' => 'Notes',
            'value' => old('notes', $workoutPlan->notes ?? null),
            'placeholder' => 'Enter any additional notes or instructions',
            'rows' => 4,
            'colspan' => 2,
        ])
    </div>
</div>

