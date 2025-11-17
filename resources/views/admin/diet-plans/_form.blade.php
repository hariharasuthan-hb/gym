@php
    $dietPlan = $dietPlan ?? null;
    $isEdit = $isEdit ?? false;
    $members = $members ?? [];
    $trainers = $trainers ?? collect();
@endphp

<div class="space-y-4">
    {{-- Plan Information Section --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl border border-blue-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Plan Information</h3>
        
        <div class="grid grid-cols-2 gap-4">
            @include('admin.components.form-input', [
                'name' => 'plan_name',
                'label' => 'Plan Name',
                'value' => old('plan_name', $dietPlan->plan_name ?? null),
                'required' => true,
                'placeholder' => 'e.g., Weight Loss Diet Plan',
            ])
            
            @include('admin.components.form-select', [
                'name' => 'member_id',
                'label' => 'Member',
                'options' => $members->pluck('name', 'id')->toArray(),
                'value' => old('member_id', $dietPlan->member_id ?? null),
                'required' => true,
                'placeholder' => 'Select a member',
            ])
            
            @if(auth()->user()->hasRole('admin') && $trainers->isNotEmpty())
                @include('admin.components.form-select', [
                    'name' => 'trainer_id',
                    'label' => 'Trainer',
                    'options' => $trainers->pluck('name', 'id')->toArray(),
                    'value' => old('trainer_id', $dietPlan->trainer_id ?? null),
                    'required' => true,
                    'placeholder' => 'Select a trainer',
                ])
            @endif
            
            @include('admin.components.form-input', [
                'name' => 'target_calories',
                'label' => 'Target Calories',
                'type' => 'number',
                'value' => old('target_calories', $dietPlan->target_calories ?? null),
                'placeholder' => 'e.g., 2000',
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
                'value' => old('status', $dietPlan->status ?? 'active'),
                'required' => true,
            ])
            
            @include('admin.components.form-input', [
                'name' => 'start_date',
                'label' => 'Start Date',
                'type' => 'date',
                'value' => old('start_date', format_date_input($dietPlan->start_date ?? null)),
                'required' => true,
            ])
            
            @include('admin.components.form-input', [
                'name' => 'end_date',
                'label' => 'End Date',
                'type' => 'date',
                'value' => old('end_date', format_date_input($dietPlan->end_date ?? null)),
            ])
        </div>
        
        @include('admin.components.form-textarea', [
            'name' => 'description',
            'label' => 'Description',
            'value' => old('description', $dietPlan->description ?? null),
            'placeholder' => 'Enter plan description',
            'rows' => 3,
            'colspan' => 2,
        ])
    </div>

    {{-- Nutritional Goals Section --}}
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-5 rounded-xl border border-green-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Nutritional Goals</h3>
        
        @include('admin.components.form-textarea', [
            'name' => 'nutritional_goals',
            'label' => 'Nutritional Goals',
            'value' => old('nutritional_goals', $dietPlan->nutritional_goals ?? null),
            'placeholder' => 'Enter nutritional goals (e.g., High protein, Low carb, etc.)',
            'rows' => 4,
            'colspan' => 2,
        ])
    </div>

    {{-- Meal Plan Section --}}
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-5 rounded-xl border border-purple-100">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-semibold text-gray-800">Meal Plan</h3>
            <button type="button" id="add-meal" class="text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Meal
            </button>
        </div>
        
        <div id="meals-container" class="space-y-3">
            @php
                // Parse existing meal plan
                $existingMeals = [];
                if ($dietPlan && $dietPlan->meal_plan) {
                    if (is_array($dietPlan->meal_plan)) {
                        $existingMeals = $dietPlan->meal_plan;
                    } else {
                        // Try to decode JSON
                        $decoded = json_decode($dietPlan->meal_plan, true);
                        $existingMeals = $decoded ? $decoded : [$dietPlan->meal_plan];
                    }
                }
                // If no existing meals, add one empty field
                if (empty($existingMeals)) {
                    $existingMeals = [''];
                }
            @endphp
            
            @foreach($existingMeals as $index => $meal)
                <div class="meal-item flex items-center gap-2">
                    <input type="text" 
                           name="meals[]" 
                           value="{{ old("meals.{$index}", is_array($meal) ? json_encode($meal) : $meal) }}"
                           placeholder="Enter meal (e.g., Breakfast: Oatmeal, Lunch: Grilled Chicken)"
                           class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
                                  focus:ring-2 focus:ring-purple-500 focus:border-purple-500 
                                  transition duration-200 ease-in-out
                                  @error('meals.*') border-red-500 focus:ring-red-500 @enderror
                                  placeholder-gray-400 text-gray-900 bg-white">
                    @if($index > 0 || count($existingMeals) > 1)
                        <button type="button" 
                                class="remove-meal text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                title="Remove meal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        
        @error('meals.*')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        
        <p class="text-xs text-gray-500 mt-3">Add meals one by one. They will be automatically saved as JSON format.</p>
        
        {{-- Hidden field to store JSON --}}
        <input type="hidden" name="meal_plan_json" id="meal_plan_json" value="">
    </div>

    {{-- Notes Section --}}
    <div class="bg-gradient-to-r from-orange-50 to-red-50 p-5 rounded-xl border border-orange-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3">Additional Notes</h3>
        
        @include('admin.components.form-textarea', [
            'name' => 'notes',
            'label' => 'Notes',
            'value' => old('notes', $dietPlan->notes ?? null),
            'placeholder' => 'Enter any additional notes or instructions',
            'rows' => 4,
            'colspan' => 2,
        ])
    </div>
</div>

