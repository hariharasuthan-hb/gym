@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
                    <p class="mt-2 text-gray-600">Stay updated with your latest notifications and announcements.</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($totalUnreadCount > 0)
                        <span id="unread-count-badge" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-semibold flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span id="unread-count-text">{{ $totalUnreadCount }} {{ Str::plural('unread', $totalUnreadCount) }}</span>
                        </span>
                        <form method="POST" action="{{ route('member.notifications.read-all') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Mark All as Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Notifications List --}}
        <div class="bg-white rounded-lg shadow-sm">
            <div class="divide-y divide-gray-200">
                {{-- Laravel Database Notifications --}}
                @forelse($dbNotifications ?? [] as $notification)
                    @php
                        $data = $notification->data ?? [];
                        $isRead = $notification->read_at !== null;
                        $icon = $data['icon'] ?? 'bell';
                    @endphp
                    <div class="p-6 hover:bg-gray-50 transition-colors {{ $isRead ? '' : 'bg-blue-50/30' }}" 
                         data-notification-row 
                         data-notification-type="db" 
                         data-notification-id="{{ $notification->id }}">
                        <div class="flex items-start gap-4">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full {{ $isRead ? 'bg-gray-100' : 'bg-blue-100' }} flex items-center justify-center">
                                    @if($icon === 'user-plus')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                        </svg>
                                    @elseif($icon === 'credit-card')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                    @elseif($icon === 'upload')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    @elseif($icon === 'video')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif($icon === 'check-circle')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @elseif($icon === 'x-circle')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @elseif($icon === 'alert-circle')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-yellow-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    @elseif($icon === 'calendar')
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $data['title'] ?? 'Notification' }}</h3>
                                            @if(!$isRead)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    New
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-gray-600 mb-2">{{ $data['message'] ?? '' }}</p>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                                            @if(isset($data['action_url']) && $data['action_url'])
                                                <a href="{{ $data['action_url'] }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    @unless($isRead)
                                        <form method="POST" action="{{ route('member.notifications.db.read', $notification->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="mark-read-btn px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                                Mark as read
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- No DB notifications --}}
                @endforelse

                {{-- In-App Notifications --}}
                @forelse($inAppNotifications ?? [] as $notification)
                    @php
                        $pivot = $notification->pivot ?? null;
                        $isRead = (bool) ($pivot?->read_at);
                    @endphp
                    <div class="p-6 hover:bg-gray-50 transition-colors {{ $isRead ? '' : 'bg-blue-50/30' }}" 
                         data-notification-row 
                         data-notification-type="inapp" 
                         data-notification-id="{{ $notification->id }}">
                        <div class="flex items-start gap-4">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full {{ $isRead ? 'bg-gray-100' : 'bg-blue-100' }} flex items-center justify-center">
                                    <svg class="w-6 h-6 {{ $isRead ? 'text-gray-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                    </svg>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $notification->title }}</h3>
                                            @if(!$isRead)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    New
                                                </span>
                                            @endif
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 capitalize">
                                                {{ $notification->audience_type }}
                                            </span>
                                        </div>
                                        <p class="text-gray-600 mb-2">{{ $notification->message }}</p>
                                        <div class="flex items-center gap-4 text-sm text-gray-500">
                                            <span>{{ ($notification->published_at ?? $notification->created_at)->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    @unless($isRead)
                                        <form method="POST" action="{{ route('member.notifications.in-app.read', $notification) }}" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="mark-read-btn px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                                Mark as read
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- No in-app notifications --}}
                @endforelse
                
                {{-- Empty State - Show only if both are empty --}}
                @if((empty($dbNotifications) || $dbNotifications->isEmpty()) && (empty($inAppNotifications) || $inAppNotifications->isEmpty()))
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                        <p class="mt-1 text-sm text-gray-500">You're all caught up! No new notifications.</p>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if(($dbNotifications && $dbNotifications->hasPages()) || ($inAppNotifications && $inAppNotifications->hasPages()))
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        @if($dbNotifications && $dbNotifications->hasPages())
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 mb-2">System Notifications</p>
                                {{ $dbNotifications->links() }}
                            </div>
                        @endif
                        @if($inAppNotifications && $inAppNotifications->hasPages())
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 mb-2">Announcements</p>
                                {{ $inAppNotifications->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.mark-read-btn');
        const unreadBadge = document.getElementById('unread-count-badge');
        const unreadCountText = document.getElementById('unread-count-text');

        buttons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const form = this.closest('form');
                const url = form.action;
                const notificationCard = this.closest('[data-notification-row]');

                if (!url || !notificationCard) {
                    return;
                }

                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = 'Marking...';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({}),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove "New" badge
                            const newBadge = notificationCard.querySelector('.bg-blue-100.text-blue-800');
                            if (newBadge) {
                                newBadge.remove();
                            }

                            // Update background
                            notificationCard.classList.remove('bg-blue-50/30');
                            
                            // Update icon background
                            const iconBg = notificationCard.querySelector('.bg-blue-100');
                            if (iconBg) {
                                iconBg.classList.remove('bg-blue-100');
                                iconBg.classList.add('bg-gray-100');
                            }

                            // Update icon color
                            const icon = notificationCard.querySelector('.text-blue-600');
                            if (icon) {
                                icon.classList.remove('text-blue-600');
                                icon.classList.add('text-gray-600');
                            }

                            // Remove button
                            this.remove();

                            // Update unread count badge
                            if (typeof data.count !== 'undefined') {
                                const count = data.count;
                                if (count > 0) {
                                    // Update badge text if element exists
                                    if (unreadCountText) {
                                        unreadCountText.textContent = count + ' ' + (count === 1 ? 'unread' : 'unreads');
                                    }
                                    // Show badge if it was hidden
                                    if (unreadBadge) {
                                        unreadBadge.classList.remove('hidden');
                                    }
                                } else {
                                    // Hide badge if count is 0
                                    if (unreadBadge) {
                                        unreadBadge.remove();
                                    }
                                    // Remove "Mark All as Read" button if count is 0
                                    const markAllBtn = document.querySelector('button[type="submit"]');
                                    if (markAllBtn && markAllBtn.textContent.includes('Mark All')) {
                                        markAllBtn.remove();
                                    }
                                }
                            }
                        } else {
                            throw new Error(data.message || 'Failed to mark as read.');
                        }
                    })
                    .catch(error => {
                        alert(error.message);
                        this.disabled = false;
                        this.textContent = originalText;
                    });
            });
        });
    });
</script>
@endpush
@endsection

