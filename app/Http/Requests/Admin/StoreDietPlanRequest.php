<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDietPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create diet plans');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'plan_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'member_id' => ['required', 'exists:users,id'],
            'target_calories' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,completed,paused,cancelled'],
            'nutritional_goals' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'meals' => ['nullable', 'array'],
            'meals.*' => ['nullable', 'string', 'max:255'],
            'meal_plan_json' => ['nullable', 'string'],
        ];
        
        // Trainer ID is required for admins, auto-set for trainers
        if (!$this->user()->hasRole('trainer')) {
            $rules['trainer_id'] = ['required', 'exists:users,id'];
        }

        // If user is trainer, they can only assign to their members
        if ($this->user()->hasRole('trainer')) {
            $rules['member_id'][] = function ($attribute, $value, $fail) {
                // Allow if member doesn't have active plan with this trainer
                $existingPlan = \App\Models\DietPlan::where('member_id', $value)
                    ->where('trainer_id', $this->user()->id)
                    ->where('status', 'active')
                    ->exists();
                
                if ($existingPlan) {
                    $fail('This member already has an active diet plan with you.');
                }
            };
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_name.required' => 'The plan name field is required.',
            'member_id.required' => 'Please select a member.',
            'member_id.exists' => 'The selected member does not exist.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be one of: active, completed, paused, cancelled.',
            'target_calories.integer' => 'The target calories must be a number.',
            'target_calories.min' => 'The target calories must be at least 1.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-set trainer_id for trainers
        if ($this->user()->hasRole('trainer')) {
            $this->merge([
                'trainer_id' => $this->user()->id,
            ]);
        }
    }
}

