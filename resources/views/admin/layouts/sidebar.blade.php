{{-- Admin Sidebar --}}
<aside class="w-64 lg:w-64 admin-sidebar text-white h-screen flex flex-col custom-scrollbar transition-all duration-300" 
       x-data="sidebarMenu()"
       x-init="init()"
       :class="{ 'lg:w-16': $root.sidebarCollapsed, 'lg:w-64': !$root.sidebarCollapsed }">
    <div class="p-4 lg:p-3 flex-1 overflow-y-auto" 
         :class="{ 'lg:px-2': $root.sidebarCollapsed }">
        @php
            $siteSettings = \App\Models\SiteSetting::getSettings();
            $landingPage = \App\Models\LandingPageContent::getActive();
            // Priority: Site Settings Logo > Landing Page Logo > Site Title
            $logo = $siteSettings->logo ?? ($landingPage->logo ?? null);
            $siteTitle = $siteSettings->site_title ?? config('app.name', 'Gym Management');
        @endphp
        <div class="mb-4" :class="{ 'lg:mb-3': $root.sidebarCollapsed }">
            @if($logo)
                <a href="{{ route('admin.dashboard') }}" class="flex items-center justify-center">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($logo) }}" 
                         alt="{{ $siteTitle }}" 
                         class="h-10 w-auto object-contain transition-all duration-300"
                         :class="{ 'lg:h-8': $root.sidebarCollapsed, 'lg:h-10': !$root.sidebarCollapsed }">
                </a>
            @else
                <h2 class="text-xl font-bold gradient-text text-center" 
                    :class="{ 'lg:text-sm lg:mb-0': $root.sidebarCollapsed }">
                    <span :class="{ 'lg:hidden': $root.sidebarCollapsed }">{{ $siteTitle }}</span>
                    <span :class="{ 'lg:hidden': !$root.sidebarCollapsed }" class="hidden lg:block">
                        {{ strtoupper(substr($siteTitle, 0, 2)) }}
                    </span>
                </h2>
            @endif
        </div>
        
        <nav class="space-y-0.5">
            {{-- Dashboard --}}
            @canany(['view users', 'view subscriptions', 'view activities', 'view reports'])
            <a href="{{ route('admin.dashboard') }}" 
               class="admin-sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
               :title="$root.sidebarCollapsed ? 'Dashboard' : ''">
                <svg class="w-5 h-5 lg:mr-0 transition-all duration-300" 
                     :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Dashboard</span>
            </a>
            @endcanany

            {{-- User Management Group --}}
            @can('view users')
            <div class="pt-1">
                <button @click="toggleGroup('users')" 
                        class="w-full flex items-center justify-between px-4 py-2 rounded-lg hover:bg-white/10 transition-all duration-200"
                        :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                        :title="$root.sidebarCollapsed ? 'User Management' : ''">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="text-sm font-semibold transition-all duration-300">User Management</span>
                    </div>
                    <svg :class="{ 'lg:hidden': $root.sidebarCollapsed }" 
                         class="w-4 h-4 transition-transform transition-all duration-300" 
                         :class="{ 'rotate-90': openGroups.users }" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="openGroups.users" x-collapse class="ml-4 mt-0.5 space-y-0.5">
                    <a href="{{ route('admin.users.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Users' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Users</span>
                    </a>
                </div>
            </div>
            @endcan

            {{-- Subscription Management Group --}}
            @canany(['view subscription plans', 'view subscriptions'])
            <div class="pt-1">
                <button @click="toggleGroup('subscriptions')" 
                        class="w-full flex items-center justify-between px-4 py-2 rounded-lg hover:bg-white/10 transition-all duration-200"
                        :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                        :title="$root.sidebarCollapsed ? 'Subscriptions' : ''">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="text-sm font-semibold transition-all duration-300">Subscriptions</span>
                    </div>
                    <svg :class="{ 'lg:hidden': $root.sidebarCollapsed }" 
                         class="w-4 h-4 transition-transform transition-all duration-300" 
                         :class="{ 'rotate-90': openGroups.subscriptions }" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="openGroups.subscriptions" x-collapse class="ml-4 mt-0.5 space-y-0.5">
                    @can('view subscription plans')
                    <a href="{{ route('admin.subscription-plans.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.subscription-plans.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Subscription Plans' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Subscription Plans</span>
                    </a>
                    @endcan
                    @can('view subscriptions')
                    <a href="{{ route('admin.subscriptions.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Subscriptions' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Subscriptions</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany

            {{-- Plans Management Group --}}
            @canany(['view workout plans', 'view diet plans'])
            <div class="pt-1">
                <button @click="toggleGroup('plans')" 
                        class="w-full flex items-center justify-between px-4 py-2 rounded-lg hover:bg-white/10 transition-all duration-200"
                        :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                        :title="$root.sidebarCollapsed ? 'Plans' : ''">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="text-sm font-semibold transition-all duration-300">Plans</span>
                    </div>
                    <svg :class="{ 'lg:hidden': $root.sidebarCollapsed }" 
                         class="w-4 h-4 transition-transform transition-all duration-300" 
                         :class="{ 'rotate-90': openGroups.plans }" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="openGroups.plans" x-collapse class="ml-4 mt-0.5 space-y-0.5">
                    @can('view workout plans')
                    <a href="{{ route('admin.workout-plans.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.workout-plans.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Workout Plans' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Workout Plans</span>
                    </a>
                    @endcan
                    @can('view diet plans')
                    <a href="{{ route('admin.diet-plans.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.diet-plans.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Diet Plans' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Diet Plans</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany

            {{-- Financial Group --}}
            @canany(['view payments', 'view invoices'])
            <div class="pt-1">
                <button @click="toggleGroup('financial')" 
                        class="w-full flex items-center justify-between px-4 py-2 rounded-lg hover:bg-white/10 transition-all duration-200"
                        :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                        :title="$root.sidebarCollapsed ? 'Financial' : ''">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="text-sm font-semibold transition-all duration-300">Financial</span>
                    </div>
                    <svg :class="{ 'lg:hidden': $root.sidebarCollapsed }" 
                         class="w-4 h-4 transition-transform transition-all duration-300" 
                         :class="{ 'rotate-90': openGroups.financial }" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="openGroups.financial" x-collapse class="ml-4 mt-0.5 space-y-0.5">
                    @can('view payments')
                    <a href="{{ route('admin.payments.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Payments' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Payments</span>
                    </a>
                    @endcan
                    @can('view invoices')
                    <a href="{{ route('admin.invoices.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Invoices' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Invoices</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany

            {{-- Activities --}}
            @can('view activities')
            <a href="{{ route('admin.activities.index') }}" 
               class="admin-sidebar-item {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}"
               :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
               :title="$root.sidebarCollapsed ? 'Activities' : ''">
                <svg class="w-5 h-5 transition-all duration-300" 
                     :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Activities</span>
            </a>
            @endcan

            {{-- CMS Management Group --}}
            @canany(['view cms pages', 'view cms content', 'view landing page', 'view site settings', 'view payment settings', 'view banners'])
            <div class="pt-1">
                <button @click="toggleGroup('cms')" 
                        class="w-full flex items-center justify-between px-4 py-2 rounded-lg hover:bg-white/10 transition-all duration-200"
                        :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                        :title="$root.sidebarCollapsed ? 'CMS' : ''">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="text-sm font-semibold transition-all duration-300">CMS</span>
                    </div>
                    <svg :class="{ 'lg:hidden': $root.sidebarCollapsed }" 
                         class="w-4 h-4 transition-transform transition-all duration-300" 
                         :class="{ 'rotate-90': openGroups.cms }" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <div x-show="openGroups.cms" x-collapse class="ml-4 mt-0.5 space-y-0.5">
                    @can('view cms pages')
                    <a href="{{ route('admin.cms.pages.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.cms.pages.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Pages' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Pages</span>
                    </a>
                    @endcan
                    @can('view cms content')
                    <a href="{{ route('admin.cms.content.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.cms.content.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Content' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Content</span>
                    </a>
                    @endcan
                    @can('view site settings')
                    <a href="{{ route('admin.site-settings.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.site-settings.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Site Settings' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Site Settings</span>
                    </a>
                    @endcan
                    @can('view payment settings')
                    <a href="{{ route('admin.payment-settings.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.payment-settings.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Payment Settings' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Payment Settings</span>
                    </a>
                    @endcan
                    @can('view banners')
                    <a href="{{ route('admin.banners.index') }}" 
                       class="admin-sidebar-item {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}"
                       :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
                       :title="$root.sidebarCollapsed ? 'Banners' : ''">
                        <svg class="w-4 h-4 transition-all duration-300" 
                             :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Banners</span>
                    </a>
                    @endcan
                </div>
            </div>
            @endcanany

            {{-- Reports --}}
            @can('view reports')
            <a href="{{ route('admin.reports.index') }}" 
               class="admin-sidebar-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
               :class="{ 'lg:justify-center lg:px-2': $root.sidebarCollapsed }"
               :title="$root.sidebarCollapsed ? 'Reports' : ''">
                <svg class="w-5 h-5 transition-all duration-300" 
                     :class="{ 'lg:mr-0': $root.sidebarCollapsed, 'lg:mr-3': !$root.sidebarCollapsed, 'mr-3': true }" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span :class="{ 'lg:hidden': $root.sidebarCollapsed }" class="transition-all duration-300">Reports</span>
            </a>
            @endcan
        </nav>
    </div>

    {{-- User Info at Bottom --}}
    <div class="w-64 p-3 border-t border-gray-700/50 bg-gray-900/50 backdrop-blur-sm flex-shrink-0 transition-all duration-300"
         :class="{ 'lg:w-16 lg:px-2': $root.sidebarCollapsed, 'lg:w-64': !$root.sidebarCollapsed }">
        <div class="flex items-center" :class="{ 'lg:justify-center': $root.sidebarCollapsed }">
            <div class="flex-1 min-w-0" :class="{ 'lg:hidden': $root.sidebarCollapsed }">
                <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>
            <div :class="{ 'lg:hidden': !$root.sidebarCollapsed }" class="hidden lg:block text-white font-bold text-sm">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ml-2 flex-shrink-0" :class="{ 'lg:ml-0': $root.sidebarCollapsed }">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-white transition-all duration-200 p-2 rounded-lg hover:bg-white/10 focus-ring" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<script>
function sidebarMenu() {
    return {
        openGroups: {
            users: {{ request()->routeIs('admin.users.*') ? 'true' : 'false' }},
            subscriptions: {{ request()->routeIs('admin.subscription-plans.*') || request()->routeIs('admin.subscriptions.*') ? 'true' : 'false' }},
            plans: {{ request()->routeIs('admin.workout-plans.*') || request()->routeIs('admin.diet-plans.*') ? 'true' : 'false' }},
            financial: {{ request()->routeIs('admin.payments.*') || request()->routeIs('admin.invoices.*') ? 'true' : 'false' }},
            cms: {{ request()->routeIs('admin.cms.*') || request()->routeIs('admin.landing-page.*') || request()->routeIs('admin.site-settings.*') || request()->routeIs('admin.payment-settings.*') || request()->routeIs('admin.banners.*') ? 'true' : 'false' }},
        },
        toggleGroup(group) {
            // Don't toggle if sidebar is collapsed
            if (this.$root.sidebarCollapsed) {
                return;
            }
            this.openGroups[group] = !this.openGroups[group];
        },
        init() {
            // Watch for sidebar collapse and close groups when collapsed
            this.$watch('$root.sidebarCollapsed', (collapsed) => {
                if (collapsed) {
                    // Close all groups when sidebar collapses
                    Object.keys(this.openGroups).forEach(key => {
                        this.openGroups[key] = false;
                    });
                }
            });
        }
    }
}
</script>
