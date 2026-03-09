@extends('admin.layouts.app')

@section('page-title', 'View Lead')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-lg font-semibold">{{ $lead->name }}</h1>
        <div class="flex space-x-3">
            @can('edit leads')
            <a href="{{ route('admin.leads.edit', $lead->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit Lead
            </a>
            @endcan
            <a href="{{ route('admin.leads.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Back to Leads
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Contact Information Card --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Contact Information</h2>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Name</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->name }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->email }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->phone ?? '-' }}</p>
                    </div>
                </div>
                
                @if($lead->message)
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Message</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->message }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Lead Management Card --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Lead Management</h2>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        @php
                            $statusColors = [
                                'new' => 'bg-blue-100 text-blue-800',
                                'contacted' => 'bg-yellow-100 text-yellow-800',
                                'qualified' => 'bg-purple-100 text-purple-800',
                                'converted' => 'bg-green-100 text-green-800',
                                'lost' => 'bg-red-100 text-red-800',
                            ];
                            $statusColor = $statusColors[$lead->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                            {{ $lead->readable_status }}
                        </span>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Source</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                            {{ $lead->readable_source }}
                        </span>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Assigned To</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->assignedTo ? $lead->assignedTo->name : 'Unassigned' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Follow Up Date</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->follow_up_date ? format_datetime_admin($lead->follow_up_date) : '-' }}</p>
                    </div>
                </div>
                
                @if($lead->notes)
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Notes</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->notes }}</p>
                    </div>
                </div>
                @endif
                
                @if($lead->converted_at)
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Converted At</p>
                        <p class="text-sm text-gray-900 font-medium">{{ format_datetime_admin($lead->converted_at) }}</p>
                    </div>
                </div>
                @endif
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Created At</p>
                        <p class="text-sm text-gray-900 font-medium">{{ format_datetime_admin($lead->created_at) }}</p>
                    </div>
                </div>
                
                @if($lead->createdBy)
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Created By</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $lead->createdBy->name }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
