{{-- About Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsAbout = $cmsAbout ?? null;
    $cmsFeatures = $cmsFeatures ?? collect();
    
    // Priority: CMS Content > Landing Page Content > Default
    $aboutTitle = $cmsAbout->title ?? $landingPage->about_title ?? 'About Us';
    $aboutDescription = $cmsAbout->content ?? $cmsAbout->description ?? $landingPage->about_description ?? 'We are dedicated to helping you achieve your fitness goals with state-of-the-art equipment, expert trainers, and a supportive community.';
    
    // Features: Use CMS features if available, otherwise use landing page features, otherwise default
    $features = [];
    if ($cmsFeatures->isNotEmpty()) {
        foreach ($cmsFeatures as $feature) {
            $features[] = [
                'icon' => $feature->extra_data['icon'] ?? '💪',
                'title' => $feature->title,
                'description' => $feature->description ?? $feature->content ?? '',
                'image' => $feature->image ? \Illuminate\Support\Facades\Storage::url($feature->image) : null,
            ];
        }
    } elseif ($landingPage && $landingPage->about_features) {
        $features = $landingPage->about_features;
    } else {
        $features = [
            ['icon' => '💪', 'title' => 'Expert Trainers', 'description' => 'Certified professionals to guide you'],
            ['icon' => '🏋️', 'title' => 'Modern Equipment', 'description' => 'Latest fitness equipment available'],
            ['icon' => '👥', 'title' => 'Community Support', 'description' => 'Join a supportive fitness community'],
        ];
    }
@endphp
<section id="about" class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4">{{ $aboutTitle }}</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                {{ $aboutDescription }}
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($features as $feature)
                <div class="text-center p-6">
                    @if(isset($feature['image']) && $feature['image'])
                        <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] ?? 'Feature' }}" class="h-16 w-16 mx-auto mb-4 rounded-full object-cover">
                    @else
                        <div class="text-4xl mb-4">{{ $feature['icon'] ?? '💪' }}</div>
                    @endif
                    <h3 class="text-xl font-semibold mb-2">{{ $feature['title'] ?? 'Feature' }}</h3>
                    <p class="text-gray-600">{{ $feature['description'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

