@extends('admin.layouts.app')

@section('page-title', 'Subscription Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscription #{{ $subscription->id }}</h1>
            <p class="text-sm text-gray-600">Gateway: {{ strtoupper($subscription->gateway) }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="btn btn-secondary">Edit</a>
            @if(!$subscription->isCanceled())
            <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST" onsubmit="return confirm('Cancel this subscription?')">
                @csrf
                <button type="submit" class="btn btn-danger">Cancel Subscription</button>
            </form>
            @endif
            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Member Information</h2>
            <div class="text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Name</span>
                    <span class="font-semibold">{{ $subscription->user->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Email</span>
                    <span class="font-semibold">{{ $subscription->user->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Plan</span>
                    <span class="font-semibold">{{ $subscription->subscriptionPlan->plan_name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $subscription->status === 'trialing' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $subscription->status === 'past_due' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $subscription->status === 'canceled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $subscription->status === 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst(str_replace('_',' ', $subscription->status)) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Billing Timeline</h2>
            <div class="text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Started</span>
                    <span class="font-semibold">{{ format_datetime_smart($subscription->started_at) ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Next Billing</span>
                    <span class="font-semibold">{{ format_datetime_smart($subscription->next_billing_at) ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Trial Ends</span>
                    <span class="font-semibold">{{ format_datetime_smart($subscription->trial_end_at) ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Canceled At</span>
                    <span class="font-semibold">{{ format_datetime_smart($subscription->canceled_at) ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($subscription->metadata && is_array($subscription->metadata) && count($subscription->metadata) > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Information</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @foreach($subscription->metadata as $key => $value)
                @if($value !== null && $value !== '')
                    <div>
                        <dt class="text-gray-500 mb-1">{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}</dt>
                        <dd class="font-semibold text-gray-900">
                            @if(is_array($value) || is_object($value))
                                {{ json_encode($value, JSON_UNESCAPED_SLASHES) }}
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endif
            @endforeach
        </dl>
    </div>
    @endif
</div>
@endsection

