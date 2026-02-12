<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create leads');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:5000',
            'status' => ['required', Rule::in(Lead::getStatusOptions())],
            'source' => ['required', Rule::in(Lead::getSourceOptions())],
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:5000',
            'follow_up_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'status.required' => 'The status field is required.',
            'status.in' => 'Please select a valid status.',
            'source.required' => 'The source field is required.',
            'source.in' => 'Please select a valid source.',
            'assigned_to.exists' => 'The selected user does not exist.',
            'follow_up_date.after_or_equal' => 'The follow-up date must be today or in the future.',
        ];
    }
}
