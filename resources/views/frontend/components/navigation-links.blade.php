{{-- Reusable Navigation Links Component --}}
@php
    $menus = \App\Models\Menu::getActiveMenus();
    $linkClass = $class ?? 'text-gray-700 hover:text-blue-600 transition';
    $isMemberDashboard = request()->routeIs('member.dashboard');
    $isMemberProfile = request()->routeIs('member.profile');
    $isMemberPage = request()->routeIs('member.*');
    // Menu items to exclude on all member pages - we show About, Services, Contact as home page section links instead
    $excludedMenusOnMemberPages = ['About', 'Services', 'Service', 'Contact Us', 'Contact'];
@endphp

@if($isMemberDashboard && auth()->check() && auth()->user()->hasRole('member'))
    {{-- Simplified navigation for member dashboard page only --}}
    <a href="{{ route('frontend.home') }}" 
       class="{{ $linkClass }}">
        Home
    </a>
    <a href="{{ route('member.profile') }}" 
       class="{{ $linkClass }}">
        Profile
    </a>
@else
    {{-- Regular navigation for all other pages --}}
    @foreach($menus as $menu)
        @if($menu->title === 'Pages')
            {{-- Skip Pages menu item --}}
            @continue
        @elseif($menu->title === 'Dashboard')
            {{-- Skip Dashboard from menu - we show it separately for authenticated users --}}
            @continue
        @elseif($isMemberPage && in_array($menu->title, $excludedMenusOnMemberPages))
            {{-- Skip certain menu items on member pages --}}
            @continue
        @else
            {{-- Regular Menu Item --}}
            <a href="{{ $menu->getFullUrlAttribute() }}" 
               target="{{ $menu->target }}"
               class="{{ $linkClass }}">
                {{ $menu->title }}
            </a>
        @endif
    @endforeach

    {{-- Home page section links for member pages --}}
    @if($isMemberPage)
        <a href="{{ route('frontend.home') }}#about"
           class="{{ $linkClass }}">
            About
        </a>
        <a href="{{ route('frontend.home') }}#services"
           class="{{ $linkClass }}">
            Services
        </a>
        <a href="{{ route('frontend.home') }}#contact"
           class="{{ $linkClass }}">
            Contact
        </a>
    @endif

    {{-- Dashboard and Profile Links for authenticated members --}}
    @auth
        @if(auth()->user()->hasRole('member'))
            <a href="{{ route('member.dashboard') }}"
               class="{{ $linkClass }}">
                Dashboard
            </a>
            <a href="{{ route('member.profile') }}"
               class="{{ $linkClass }}">
                Profile
            </a>
        @elseif(auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.dashboard') }}"
               class="{{ $linkClass }}">
                Admin
            </a>
        @endif
    @endauth
@endif

