{{-- Admin Header --}}
<header class="admin-header">
    <div class="px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Sidebar Toggle Button (Mobile - open/close drawer) --}}
                <button
                    type="button"
                    @click="$dispatch('toggle-sidebar')"
                    class="lg:hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring"
                    title="Toggle Sidebar"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                {{-- Collapse Toggle Button (Desktop - shrink/expand sidebar) --}}
                <button
                    type="button"
                    id="sidebar-toggle"
                    class="hidden lg:flex p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring"
                    title="Toggle Sidebar Width"
                >
                    <svg id="sidebar-toggle-expand" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                    <svg id="sidebar-toggle-collapse" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                </button>

                {{-- Back Button (after toggle buttons) --}}
                <button 
                    type="button"
                    onclick="window.history.back();"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring"
                    title="Go Back"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span>Back</span>
                </button>

                <div class="page-header">
                    <h1 class="page-title">
                        @yield('page-title', 'Admin Dashboard')
                    </h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                {{-- Notifications (only show if user can access notification center) --}}
                @canany(['view announcements', 'view notifications'])
                    @php
                        try {
                            $inAppNotificationRepository = app(\App\Repositories\Interfaces\InAppNotificationRepositoryInterface::class);
                            $dbNotificationRepository = app(\App\Repositories\Interfaces\NotificationRepositoryInterface::class);
                            $inAppCount = $inAppNotificationRepository->getUnreadCountForUser(Auth::user());
                            $dbCount = $dbNotificationRepository->getUnreadCountForUser(Auth::user());
                            $notificationCount = $inAppCount + $dbCount;
                        $notificationBadge = $notificationCount > 99 ? '99+' : $notificationCount;
                        } catch (\Exception $e) {
                            $notificationCount = 0;
                            $notificationBadge = 0;
                        }
                    @endphp
                    <a href="{{ route('admin.notification-center.index') }}"
                       class="relative p-2.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring"
                       title="Notification Center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        @if($notificationCount > 0)
                            <span id="notification-count-badge" class="absolute -top-1 -right-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-red-600 text-xs font-semibold text-white ring-2 ring-white shadow-sm" style="padding-left:0.30rem;padding-right:0.30rem;">
                                <span id="notification-count-value">{{ $notificationBadge }}</span>
                            </span>
                        @endif
                    </a>
                @endcanany

                {{-- User Menu Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button 
                        @click="open = !open"
                        class="flex items-center space-x-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-xl p-1 transition-all duration-200"
                    >
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                        </div>
                        <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-primary-600 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-primary-500/30 hover:shadow-xl transition-shadow duration-200">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <svg class="w-4 h-4 text-gray-600 transition-transform duration-200" 
                             :class="{ 'rotate-180': open }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 overflow-hidden"
                         style="display: none;">
                        <div class="py-1">
                            {{-- User Info Header --}}
                            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                <p class="text-sm font-semibold text-gray-900 leading-tight">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                            </div>

                            {{-- Profile Link --}}
                            <a href="{{ route('admin.profile.edit') }}" 
                               @click="open = false"
                               class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <svg class="w-5 h-5 mr-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="flex-1">My Profile</span>
                            </a>

                            {{-- Divider --}}
                            <div class="border-t border-gray-200 my-1"></div>

                            {{-- Logout Button --}}
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" 
                                        class="w-full flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200 text-left">
                                    <svg class="w-5 h-5 mr-3 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span class="flex-1">Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>



