{{-- Services Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsServicesSection = $cmsServicesSection ?? null;
    $cmsServices = $cmsServices ?? collect();
    
    // Priority: CMS Content > Landing Page Content > Default
    $servicesTitle = ($cmsServicesSection && $cmsServicesSection->title) 
        ? $cmsServicesSection->title 
        : ($landingPage->services_title ?? 'Our Services');
    $servicesDescription = ($cmsServicesSection && ($cmsServicesSection->description ?? $cmsServicesSection->content)) 
        ? ($cmsServicesSection->description ?? $cmsServicesSection->content) 
        : ($landingPage->services_description ?? 'Choose from our range of fitness programs and services');
    
    // Check for background video/image: First check services-section, then check all services content
    $servicesBackgroundVideo = null;
    $servicesBackgroundImage = null;
    if ($cmsServicesSection) {
        if ($cmsServicesSection->background_video) {
            $servicesBackgroundVideo = \Illuminate\Support\Facades\Storage::url($cmsServicesSection->background_video);
        } elseif ($cmsServicesSection->background_image) {
            $servicesBackgroundImage = \Illuminate\Support\Facades\Storage::url($cmsServicesSection->background_image);
        }
    }
    
    if (!$servicesBackgroundVideo && !$servicesBackgroundImage) {
        // Check all services content (including the section if it's in the collection)
        $cmsContentRepo = app(\App\Repositories\Interfaces\CmsContentRepositoryInterface::class);
        $allServicesForBg = $cmsContentRepo->getFrontendContent('services');
        
        // Find first service with background_video or background_image
        foreach ($allServicesForBg as $serviceItem) {
            if ($serviceItem->background_video) {
                $servicesBackgroundVideo = \Illuminate\Support\Facades\Storage::url($serviceItem->background_video);
                break;
            } elseif ($serviceItem->background_image) {
                $servicesBackgroundImage = \Illuminate\Support\Facades\Storage::url($serviceItem->background_image);
                break;
            }
        }
    }
    
    // Services: Use CMS services if available, otherwise use landing page services, otherwise default
    $services = [];
    if ($cmsServices->isNotEmpty()) {
        foreach ($cmsServices as $service) {
            $services[] = [
                'title' => $service->title,
                'description' => $service->description ?? $service->content ?? '',
                'image' => $service->image ? \Illuminate\Support\Facades\Storage::url($service->image) : null,
                'link' => $service->link ?? '#contact',
                'link_text' => $service->link_text ?? 'Learn More',
            ];
        }
    } elseif ($landingPage && $landingPage->services) {
        $services = $landingPage->services;
    } else {
        $services = [
            ['title' => 'Personal Training', 'description' => 'One-on-one training sessions with expert trainers'],
            ['title' => 'Group Classes', 'description' => 'Join group fitness classes for motivation'],
            ['title' => 'Nutrition Plans', 'description' => 'Customized diet plans for your goals'],
            ['title' => 'Cardio Zone', 'description' => 'State-of-the-art cardio equipment'],
            ['title' => 'Weight Training', 'description' => 'Comprehensive weight training facilities'],
            ['title' => 'Yoga & Meditation', 'description' => 'Relax and rejuvenate with yoga classes'],
        ];
    }
@endphp
@php
    $hasBackground = $servicesBackgroundVideo || $servicesBackgroundImage;
    $servicesBgStyle = $servicesBackgroundImage && !$servicesBackgroundVideo
        ? "background-image: url('{$servicesBackgroundImage}'); background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed;"
        : '';
@endphp
<section id="services" class="py-12 sm:py-16 md:py-20 lg:py-24 {{ $hasBackground ? 'relative min-h-[400px] sm:min-h-[500px] md:min-h-[600px] overflow-hidden' : '' }}" style="{{ $servicesBgStyle }}">
    @if($servicesBackgroundVideo)
        <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover z-0">
            <source src="{{ $servicesBackgroundVideo }}" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @elseif($servicesBackgroundImage)
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @endif
    <div class="container mx-auto px-3 sm:px-4 md:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-8 sm:mb-10 md:mb-12">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 break-words leading-tight px-2 sm:px-0 {{ $hasBackground ? 'text-white' : 'text-gray-900' }}" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{!! render_content($servicesTitle) !!}</h2>
            <p class="{{ $hasBackground ? 'text-white' : 'text-gray-600' }} max-w-2xl mx-auto text-sm sm:text-base md:text-lg px-2 sm:px-0 break-words leading-relaxed" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                {!! render_content($servicesDescription) !!}
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            @foreach($services as $service)
                <div class="bg-white rounded-lg shadow-lg p-5 sm:p-6 hover:shadow-xl transition">
                    @if(isset($service['image']) && $service['image'])
                        <img src="{{ $service['image'] }}" alt="{{ $service['title'] ?? 'Service' }}" class="w-full h-40 sm:h-48 object-cover rounded-lg mb-4">
                    @endif
                    <h3 class="text-xl sm:text-2xl font-semibold mb-2 sm:mb-3 break-words leading-tight" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{!! render_content($service['title'] ?? 'Service') !!}</h3>
                    <p class="text-sm sm:text-base text-gray-600 mb-4 break-words leading-relaxed" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{!! render_content($service['description'] ?? '') !!}</p>
                    <a href="{{ $service['link'] ?? '#contact' }}" class="text-blue-600 font-semibold text-sm sm:text-base sm:whitespace-nowrap">
                        {{ $service['link_text'] ?? 'Learn More' }} →
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

