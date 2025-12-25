<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create workout plans');
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
            'duration_weeks' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'status' => ['required', 'in:active,completed,paused,cancelled'],
            'notes' => ['nullable', 'string'],
            'exercises' => ['nullable', 'array'],
            'exercises.*' => ['nullable', 'string', 'max:255'],
            'exercises_json' => ['nullable', 'string'],
            'demo_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:25600'], // Max 25MB
            // When the admin UI uploads via AJAX (chunked), it sends a stored path instead of a file
            'demo_video_path' => ['nullable', 'string', 'max:1000'],
        ];
        
        // Trainer ID is required for admins, auto-set for trainers
        if (!$this->user()->hasRole('trainer')) {
            $rules['trainer_id'] = ['required', 'exists:users,id'];
        }

        // If user is trainer, they can only assign to their members
        if ($this->user()->hasRole('trainer')) {
            $memberIds = \App\Models\WorkoutPlan::where('trainer_id', $this->user()->id)
                ->pluck('member_id')
                ->unique()
                ->toArray();
            
            // Also allow any member that doesn't have a plan yet
            $rules['member_id'][] = function ($attribute, $value, $fail) {
                // Allow if member doesn't have active plan with this trainer
                $existingPlan = \App\Models\WorkoutPlan::where('member_id', $value)
                    ->where('trainer_id', $this->user()->id)
                    ->where('status', 'active')
                    ->exists();
                
                if ($existingPlan) {
                    $fail('This member already has an active workout plan with you.');
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
            'demo_video.file' => 'The demo video must be a valid file.',
            'demo_video.mimes' => 'Demo video must be in mp4, webm, or mov format.',
            'demo_video.max' => 'Demo video file size must not exceed 25MB.',
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

