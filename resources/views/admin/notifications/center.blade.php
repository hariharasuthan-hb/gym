@extends('admin.layouts.app')

@section('page-title', 'Notification Center')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">Notification Center</h1>
            <p class="text-sm text-gray-500 mt-1">Stay updated with announcements and targeted alerts.</p>
        </div>
        @php
            $user = auth()->user();
            $inAppNotificationRepository = app(\App\Repositories\Interfaces\InAppNotificationRepositoryInterface::class);
            $dbNotificationRepository = app(\App\Repositories\Interfaces\NotificationRepositoryInterface::class);
            $inAppUnreadCount = $inAppNotificationRepository->getUnreadCountForUser($user);
            $dbUnreadCount = $dbNotificationRepository->getUnreadCountForUser($user);
            $totalUnreadCount = $inAppUnreadCount + $dbUnreadCount;
        @endphp
        @if($totalUnreadCount > 0 && $showMarkRead)
            <form method="POST" action="{{ route('admin.notification-center.read-all') }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Mark All as Read
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1 space-y-4">
            <div class="admin-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Latest Announcements</h2>
                </div>
                <div class="space-y-4">
                    @forelse($announcements as $announcement)
                        <div class="p-3 rounded-lg border border-gray-200">
                            <p class="text-sm text-gray-500 uppercase tracking-wide">{{ ucfirst($announcement->audience_type) }}</p>
                            <h3 class="text-base font-semibold text-gray-900">{{ $announcement->title }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 140) }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ format_datetime_admin($announcement->published_at) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No announcements yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="xl:col-span-2 space-y-4">
            <div class="admin-card p-0">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    {{-- Laravel Database Notifications --}}
                    @forelse($dbNotifications ?? [] as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $isRead = $notification->read_at !== null;
                        @endphp
                        <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4" data-notification-row data-notification-type="db" data-notification-id="{{ $notification->id }}">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900">{{ $data['title'] ?? 'Notification' }}</span>
                                    @if(isset($data['type']))
                                        <span class="badge badge-light capitalize">{{ str_replace('_', ' ', $data['type']) }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600 mt-1">{{ $data['message'] ?? '' }}</p>
                                <p class="text-xs text-gray-400 mt-2">Sent {{ format_datetime_admin($notification->created_at) }}</p>
                            </div>
                            @if($showMarkRead)
                                <div class="flex items-center gap-3">
                                    <span class="mark-read-status text-sm {{ $isRead ? 'text-green-600' : 'text-yellow-600' }}">
                                        {{ $isRead ? 'Read' : 'Unread' }}
                                    </span>
                                    @unless($isRead)
                                        <button type="button"
                                                class="btn btn-sm btn-primary mark-read-btn"
                                                data-read-url="{{ route('admin.notification-center.db.read', $notification->id) }}"
                                                data-notification-id="{{ $notification->id }}">
                                            Mark as read
                                        </button>
                                    @endunless
                                </div>
                            @endif
                        </div>
                    @empty
                    @endforelse
                    {{-- In-App Notifications --}}
                    @forelse($notifications as $notification)
                        @php
                            $pivot = $notification->pivot ?? null;
                            $isRead = (bool) ($pivot?->read_at);
                        @endphp
                        <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4" data-notification-row data-notification-type="inapp" data-notification-id="{{ $notification->id }}">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900">{{ $notification->title }}</span>
                                    <span class="badge badge-light capitalize">{{ $notification->audience_type }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">{{ $notification->message }}</p>
                                <p class="text-xs text-gray-400 mt-2">Sent {{ format_datetime_admin($notification->published_at ?? $notification->created_at) }}</p>
                            </div>
                            @if($showMarkRead)
                                <div class="flex items-center gap-3">
                                    <span class="mark-read-status text-sm {{ $isRead ? 'text-green-600' : 'text-yellow-600' }}">
                                        {{ $isRead ? 'Read' : 'Unread' }}
                                    </span>
                                    @unless($isRead)
                                        <button type="button"
                                                class="btn btn-sm btn-primary mark-read-btn"
                                                data-read-url="{{ route('admin.notification-center.read', $notification) }}"
                                                data-notification-id="{{ $notification->id }}">
                                            Mark as read
                                        </button>
                                    @endunless
                                </div>
                            @endif
                        </div>
                    @empty
                        @if(empty($dbNotifications) || $dbNotifications->isEmpty())
                            <p class="p-6 text-center text-gray-500">No notifications yet.</p>
                        @endif
                    @endforelse
                </div>
                <div class="px-6 py-4">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.mark-read-btn');
            const badge = document.getElementById('notification-count-badge');
            const badgeValue = document.getElementById('notification-count-value');

            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const url = this.dataset.readUrl;
                    const notificationCard = this.closest('[data-notification-row]');

                    if (!url || !notificationCard) {
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Marking...';

                    const notificationType = button.closest('[data-notification-row]').dataset.notificationType;

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
                                notificationCard.querySelector('.mark-read-status').textContent = 'Read';
                                notificationCard.querySelector('.mark-read-status').classList.remove('text-yellow-600');
                                notificationCard.querySelector('.mark-read-status').classList.add('text-green-600');
                                button.remove();

                                if (badge && badgeValue && typeof data.count !== 'undefined') {
                                    const count = data.count;
                                    if (count > 0) {
                                        badge.classList.remove('hidden');
                                        badgeValue.textContent = count > 99 ? '99+' : count;
                                    } else {
                                        badge.classList.add('hidden');
                                        badgeValue.textContent = '0';
                                    }
                                }
                            } else {
                                throw new Error(data.message || 'Failed to mark as read.');
                            }
                        })
                        .catch(error => {
                            alert(error.message);
                            button.disabled = false;
                            button.textContent = 'Mark as read';
                        });
                });
            });
        });
    </script>
@endpush

