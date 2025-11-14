{{-- Services Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsServicesSection = $cmsServicesSection ?? null;
    $cmsServices = $cmsServices ?? collect();
    
    // Priority: CMS Content > Landing Page Content > Default
    $servicesTitle = $cmsServicesSection->title ?? $landingPage->services_title ?? 'Our Services';
    $servicesDescription = $cmsServicesSection->description ?? $cmsServicesSection->content ?? $landingPage->services_description ?? 'Choose from our range of fitness programs and services';
    
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
<section id="services" class="py-20">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4">{{ $servicesTitle }}</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                {{ $servicesDescription }}
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($services as $service)
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                    @if(isset($service['image']) && $service['image'])
                        <img src="{{ $service['image'] }}" alt="{{ $service['title'] ?? 'Service' }}" class="w-full h-48 object-cover rounded-lg mb-4">
                    @endif
                    <h3 class="text-2xl font-semibold mb-3">{{ $service['title'] ?? 'Service' }}</h3>
                    <p class="text-gray-600 mb-4">{{ $service['description'] ?? '' }}</p>
                    <a href="{{ $service['link'] ?? '#contact' }}" class="text-blue-600 font-semibold">
                        {{ $service['link_text'] ?? 'Learn More' }} →
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

