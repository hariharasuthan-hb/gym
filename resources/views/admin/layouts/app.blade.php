<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin Portal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/admin/app.css', 'resources/js/admin/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="font-sans antialiased custom-scrollbar" x-data="{ sidebarOpen: false }" @toggle-sidebar.window="sidebarOpen = !sidebarOpen">
    <div class="h-screen flex overflow-hidden">
        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden"></div>
        
        {{-- Sidebar --}}
        <div :class="{ '-translate-x-full lg:translate-x-0': !sidebarOpen, 'translate-x-0': sidebarOpen }"
             class="fixed lg:static inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out lg:transition-none">
            @include('admin.layouts.sidebar')
        </div>
        
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col w-full overflow-hidden">
            {{-- Header --}}
            @include('admin.layouts.header')
            
            {{-- Page Content --}}
            <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
    
    @include('admin.components.confirm-modal')
    @stack('scripts')
    
    <script>
        // Sidebar Collapse Toggle (Vanilla JS)
        (function() {
            const sidebar = document.getElementById('admin-sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const sidebarContent = document.getElementById('sidebar-content');
            const sidebarTitle = document.getElementById('sidebar-title');
            const sidebarTitleFull = document.getElementById('sidebar-title-full');
            const sidebarTitleShort = document.getElementById('sidebar-title-short');
            const sidebarLogo = document.getElementById('sidebar-logo');
            const toggleExpand = document.getElementById('sidebar-toggle-expand');
            const toggleCollapse = document.getElementById('sidebar-toggle-collapse');
            
            if (!sidebar || !toggleBtn) return;
            
            let collapsed = false;
            
            // Load saved state
            try {
                collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (collapsed) {
                    applyCollapsedState();
                }
            } catch(e) {
                console.warn('Could not load sidebar state:', e);
            }
            
            function applyCollapsedState() {
                // Get all menu text spans (not in SVG)
                const menuTexts = document.querySelectorAll('#sidebar-nav span:not(svg span)');
                const menuItems = document.querySelectorAll('#sidebar-nav .admin-sidebar-item, #sidebar-nav > div > button');
                
                if (collapsed) {
                    sidebar.classList.remove('w-64', 'lg:w-64');
                    sidebar.classList.add('w-16', 'lg:w-16');
                    if (sidebarContent) sidebarContent.classList.add('lg:px-2');
                    
                    // Hide menu texts
                    menuTexts.forEach(el => {
                        if (el && !el.closest('svg')) {
                            el.classList.add('hidden');
                        }
                    });
                    
                    // Hide full title, show short
                    if (sidebarTitleFull) sidebarTitleFull.classList.add('hidden');
                    if (sidebarTitleShort) sidebarTitleShort.classList.remove('hidden');
                    if (sidebarLogo) {
                        sidebarLogo.classList.remove('h-10');
                        sidebarLogo.classList.add('h-8');
                    }
                    
                    // Update toggle icons
                    if (toggleExpand) toggleExpand.classList.add('hidden');
                    if (toggleCollapse) toggleCollapse.classList.remove('hidden');
                    
                    // Center align menu items
                    menuItems.forEach(el => {
                        el.classList.add('lg:justify-center', 'lg:px-2');
                    });
                } else {
                    sidebar.classList.remove('w-16', 'lg:w-16');
                    sidebar.classList.add('w-64', 'lg:w-64');
                    if (sidebarContent) sidebarContent.classList.remove('lg:px-2');
                    
                    // Show menu texts
                    menuTexts.forEach(el => {
                        if (el && !el.closest('svg')) {
                            el.classList.remove('hidden');
                        }
                    });
                    
                    // Show full title, hide short
                    if (sidebarTitleFull) sidebarTitleFull.classList.remove('hidden');
                    if (sidebarTitleShort) sidebarTitleShort.classList.add('hidden');
                    if (sidebarLogo) {
                        sidebarLogo.classList.remove('h-8');
                        sidebarLogo.classList.add('h-10');
                    }
                    
                    // Update toggle icons
                    if (toggleExpand) toggleExpand.classList.remove('hidden');
                    if (toggleCollapse) toggleCollapse.classList.add('hidden');
                    
                    // Remove center align
                    menuItems.forEach(el => {
                        el.classList.remove('lg:justify-center', 'lg:px-2');
                    });
                }
            }
            
            toggleBtn.addEventListener('click', () => {
                collapsed = !collapsed;
                applyCollapsedState();
                
                try {
                    localStorage.setItem('sidebarCollapsed', collapsed);
                } catch(e) {
                    console.warn('Could not save sidebar state:', e);
                }
            });
            
            // Close sidebar on mobile when clicking nav links
            const sidebarNav = document.getElementById('sidebar-nav');
            if (sidebarNav) {
                sidebarNav.addEventListener('click', (e) => {
                    const link = e.target.closest('a');
                    if (link && window.innerWidth < 1024) {
                        const body = document.body;
                        if (body && body.__x && body.__x.$data) {
                            body.__x.$data.sidebarOpen = false;
                        }
                    }
                });
            }
        })();
    </script>
</body>
</html>

