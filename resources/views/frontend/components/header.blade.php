{{-- Header/Navigation --}}
@php
    $menus = \App\Models\Menu::getActiveMenus();
@endphp

<header class="bg-white shadow-md sticky top-0 z-50">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="text-2xl font-bold text-blue-600">
                @php
                    $siteSettings = \App\Models\SiteSetting::getSettings();
                    $landingPage = \App\Models\LandingPageContent::getActive();
                    // Priority: Site Settings Logo > Landing Page Logo > Site Title
                    $logo = $siteSettings->logo ?? ($landingPage->logo ?? null);
                    $siteTitle = $siteSettings->site_title ?? 'Gym Management';
                @endphp
                @if($logo)
                    <a href="{{ route('frontend.home') }}">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($logo) }}" alt="{{ $siteTitle }}" class="h-12 object-contain">
                    </a>
                @else
                    <a href="{{ route('frontend.home') }}">{{ $siteTitle }}</a>
                @endif
            </div>
            
            <div class="hidden md:flex space-x-6 items-center">
                @include('frontend.components.navigation-links', ['class' => 'text-gray-700 hover:text-blue-600 transition'])
                
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-700 hover:text-blue-600 transition">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-blue-600 transition">Login</a>
                    <a href="{{ route('frontend.register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Register</a>
                @endauth
            </div>
            
            {{-- Mobile Menu Button --}}
            <button class="md:hidden text-gray-700" id="mobile-menu-btn">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </nav>
</header>
