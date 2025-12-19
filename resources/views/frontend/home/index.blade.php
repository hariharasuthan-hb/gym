@extends('frontend.layouts.app')

@section('content')
    {{-- Banner Carousel Section --}}
    @include('frontend.components.banner-carousel')
    
    {{-- Hero Section (Fallback if no banners) --}}
    @php
        $banners = \App\Models\Banner::getActiveBanners();
    @endphp
    @if($banners->isEmpty())
        @include('frontend.components.hero', [
            'landingPage' => $landingPage ?? null,
            'cmsHero' => $cmsHero ?? null
        ])
    @endif
    
    {{-- About Section --}}
    @include('frontend.components.about', [
        'landingPage' => $landingPage ?? null,
        'cmsAbout' => $cmsAbout ?? null,
        'cmsFeatures' => $cmsFeatures ?? collect()
    ])
    
    {{-- Services Section --}}
    @include('frontend.components.services', [
        'landingPage' => $landingPage ?? null,
        'cmsServicesSection' => $cmsServicesSection ?? null,
        'cmsServices' => $cmsServices ?? collect()
    ])

    {{-- BMI Calculator Section --}}
    @if(isset($cmsBmiCalculator) && $cmsBmiCalculator && $cmsBmiCalculator->is_active)
        @include('frontend.components.bmi-calculator', [
            'cmsBmiCalculator' => $cmsBmiCalculator
        ])
    @else
        {{-- Default BMI Calculator (fallback if no CMS content) --}}
        @include('frontend.components.bmi-calculator')
    @endif

    {{-- Testimonials Section (if CMS content exists) --}}
    @if(isset($cmsTestimonials) && $cmsTestimonials->isNotEmpty())
        @include('frontend.components.testimonials', [
            'cmsTestimonialsSection' => $cmsTestimonialsSection ?? null,
            'cmsTestimonials' => $cmsTestimonials
        ])
    @endif
    
    {{-- Contact Section --}}
    @include('frontend.components.contact-form')
@endsection

