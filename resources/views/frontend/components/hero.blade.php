{{-- Hero Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsHero = $cmsHero ?? null;
    
    // Priority: CMS Content > Landing Page Content > Default
    $heroTitle = $cmsHero->title ?? $landingPage->welcome_title ?? 'Welcome to Our Gym';
    $heroSubtitle = $cmsHero->description ?? $cmsHero->content ?? $landingPage->welcome_subtitle ?? 'Transform your body, transform your life';

    // Background media priority: Video > Background Image > Image > Landing Page Image
    $heroVideo = ($cmsHero && $cmsHero->video_path && $cmsHero->video_is_background)
        ? \Illuminate\Support\Facades\Storage::url($cmsHero->video_path)
        : null;

    $heroImage = (!$heroVideo && $cmsHero && $cmsHero->background_image)
        ? \Illuminate\Support\Facades\Storage::url($cmsHero->background_image)
        : ((!$heroVideo && $cmsHero && $cmsHero->image)
            ? \Illuminate\Support\Facades\Storage::url($cmsHero->image) 
            : ((!$heroVideo && $landingPage && $landingPage->hero_background_image)
                ? \Illuminate\Support\Facades\Storage::url($landingPage->hero_background_image) 
                : null));

    $heroLink = $cmsHero->link ?? '#register';
    $heroLinkText = $cmsHero->link_text ?? 'Join Now';

    // Hero text colors
    $titleColor = $cmsHero->title_color ?? '#ffffff';
    $descriptionColor = $cmsHero->description_color ?? '#ffffff';
    $contentColor = $cmsHero->content_color ?? '#ffffff';
    
    $bgStyle = $heroImage ? "background-image: url('{$heroImage}'); background-size: cover; background-position: center; background-blend-mode: overlay;" : '';
@endphp
<section class="hero-section {{ (!$heroImage && !$heroVideo) ? 'bg-gradient-to-r from-blue-600 to-purple-600' : '' }} text-white py-20 relative overflow-hidden">
    @if($heroVideo)
        {{-- Background Video --}}
        <video autoplay muted loop class="absolute inset-0 w-full h-full object-cover z-0">
            <source src="{{ $heroVideo }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="absolute inset-0 bg-black bg-opacity-50 z-0"></div>
    @elseif($heroImage)
        {{-- Background Image --}}
        <div class="absolute inset-0 bg-black bg-opacity-50 z-0" style="{{ $bgStyle }}"></div>
    @endif
    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center">
            <h1 class="text-5xl font-bold mb-4" style="color: {{ $titleColor }}">{!! render_content($heroTitle) !!}</h1>
            <p class="text-xl mb-8" style="color: {{ $descriptionColor }}">{!! render_content($heroSubtitle) !!}</p>
            <div class="flex justify-center gap-4">
                <a href="{{ $heroLink }}" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition" style="color: {{ $contentColor }}">
                    {{ $heroLinkText }}
                </a>
                <a href="#about" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition" style="color: {{ $contentColor }}">
                    Learn More
                </a>
            </div>
        </div>
    </div>
</section>

