<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadWorkoutVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workoutPlan = $this->route('workoutPlan');
        
        // Ensure the plan belongs to the authenticated member
        return $workoutPlan && $workoutPlan->member_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'exercise_name' => ['required', 'string', 'max:255'],
            'video' => [
                'required',
                'file',
                'mimes:mp4,webm,mov',
                'max:102400', // Max 100MB
            ],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'exercise_name.required' => 'Exercise name is required.',
            'exercise_name.string' => 'Exercise name must be a valid string.',
            'exercise_name.max' => 'Exercise name must not exceed 255 characters.',
            'video.required' => 'Video file is required.',
            'video.file' => 'The uploaded file must be a valid file.',
            'video.mimes' => 'Video must be in mp4, webm, or mov format.',
            'video.max' => 'Video file size must not exceed 100MB.',
            'duration_seconds.integer' => 'Duration must be a valid number.',
            'duration_seconds.min' => 'Duration must be at least 1 second.',
            'duration_seconds.max' => 'Duration must not exceed 120 seconds.',
        ];
    }
}
