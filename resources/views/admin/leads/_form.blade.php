@php
    $lead = $lead ?? null;
    $users = $users ?? [];
    $statusOptions = $statusOptions ?? [];
    $sourceOptions = $sourceOptions ?? [];
    $isEdit = $isEdit ?? false;
@endphp

<div class="space-y-4">
    {{-- Contact Information Section --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl border border-blue-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Contact Information
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            @include('admin.components.form-input', [
                'name' => 'name',
                'label' => 'Full Name',
                'value' => $lead->name ?? null,
                'required' => true,
                'placeholder' => 'Enter full name',
            ])
            
            @include('admin.components.form-input', [
                'name' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'value' => $lead->email ?? null,
                'required' => true,
                'placeholder' => 'user@example.com',
            ])
            
            @include('admin.components.form-input', [
                'name' => 'phone',
                'label' => 'Phone Number',
                'type' => 'tel',
                'value' => $lead->phone ?? null,
                'placeholder' => '+1 (555) 123-4567',
            ])
            
            @include('admin.components.form-textarea', [
                'name' => 'message',
                'label' => 'Message',
                'value' => $lead->message ?? null,
                'placeholder' => 'Enter message or inquiry',
                'rows' => 4,
                'colspan' => 2,
            ])
        </div>
    </div>

    {{-- Lead Management Section --}}
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-5 rounded-xl border border-green-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Lead Management
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            @php
                $statusSelectOptions = ['' => 'Select Status'];
                foreach ($statusOptions as $status) {
                    $statusSelectOptions[$status] = ucfirst($status);
                }
            @endphp
            @include('admin.components.form-select', [
                'name' => 'status',
                'label' => 'Status',
                'options' => $statusSelectOptions,
                'value' => $lead->status ?? 'new',
                'required' => true,
            ])
            
            @php
                $sourceSelectOptions = ['' => 'Select Source'];
                foreach ($sourceOptions as $source) {
                    $sourceSelectOptions[$source] = ucwords(str_replace('_', ' ', $source));
                }
            @endphp
            @include('admin.components.form-select', [
                'name' => 'source',
                'label' => 'Source',
                'options' => $sourceSelectOptions,
                'value' => $lead->source ?? 'website',
                'required' => true,
            ])
            
            @php
                $userSelectOptions = ['' => 'Unassigned'];
                foreach ($users as $user) {
                    $userSelectOptions[$user->id] = $user->name;
                }
            @endphp
            @include('admin.components.form-select', [
                'name' => 'assigned_to',
                'label' => 'Assigned To',
                'options' => $userSelectOptions,
                'value' => $lead->assigned_to ?? null,
            ])
            
            @include('admin.components.form-input', [
                'name' => 'follow_up_date',
                'label' => 'Follow Up Date',
                'type' => 'datetime-local',
                'value' => $lead->follow_up_date ? $lead->follow_up_date->format('Y-m-d\TH:i') : null,
                'placeholder' => 'Select follow-up date',
            ])
            
            @include('admin.components.form-textarea', [
                'name' => 'notes',
                'label' => 'Notes',
                'value' => $lead->notes ?? null,
                'placeholder' => 'Enter additional notes or comments',
                'rows' => 4,
                'colspan' => 2,
            ])
        </div>
    </div>
</div>
