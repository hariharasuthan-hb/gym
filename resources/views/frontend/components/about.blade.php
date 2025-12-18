{{-- About Section --}}
@php
    $landingPage = $landingPage ?? \App\Models\LandingPageContent::getActive();
    $cmsAbout = $cmsAbout ?? null;
    $cmsFeatures = $cmsFeatures ?? collect();
    
    // Priority: CMS Content > Landing Page Content > Default
    $aboutTitle = ($cmsAbout && $cmsAbout->title) ? $cmsAbout->title : ($landingPage->about_title ?? 'About Us');
    $aboutDescription = ($cmsAbout && ($cmsAbout->content ?? $cmsAbout->description)) 
        ? ($cmsAbout->content ?? $cmsAbout->description) 
        : ($landingPage->about_description ?? 'We are dedicated to helping you achieve your fitness goals with state-of-the-art equipment, expert trainers, and a supportive community.');
    $aboutImage = ($cmsAbout && $cmsAbout->image) 
        ? \Illuminate\Support\Facades\Storage::url($cmsAbout->image) 
        : null;
    $aboutBackgroundImage = ($cmsAbout && $cmsAbout->background_image) 
        ? \Illuminate\Support\Facades\Storage::url($cmsAbout->background_image) 
        : null;
    
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
@php
    $aboutBgStyle = $aboutBackgroundImage 
        ? "background-image: url('{$aboutBackgroundImage}'); background-size: cover; background-position: center; background-attachment: fixed;"
        : '';
@endphp
<section id="about" class="py-12 sm:py-16 md:py-20 lg:py-24 {{ $aboutBackgroundImage ? 'relative' : 'bg-gray-50' }}" style="{{ $aboutBgStyle }}">
    @if($aboutBackgroundImage)
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @endif
    <div class="container mx-auto px-3 sm:px-4 md:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-8 sm:mb-10 md:mb-12">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 break-words leading-tight px-2 sm:px-0 {{ $aboutBackgroundImage ? 'text-white' : 'text-gray-900' }}" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{!! render_content($aboutTitle) !!}</h2>
            @if($aboutDescription && !$aboutImage)
                <p class="{{ $aboutBackgroundImage ? 'text-white' : 'text-gray-600' }} max-w-2xl mx-auto text-sm sm:text-base md:text-lg px-2 sm:px-0 break-words leading-relaxed" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                    {!! render_content($aboutDescription) !!}
                </p>
            @endif
        </div>
        
        {{-- About Content with Image --}}
        @if($aboutImage)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-10 md:gap-12 items-center mb-12 sm:mb-14 md:mb-16">
                <div class="order-2 lg:order-1">
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl group">
                        <img src="{{ $aboutImage }}" alt="{{ $aboutTitle }}" 
                             class="w-full h-[300px] sm:h-[400px] md:h-[500px] object-cover transition-transform duration-300 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <div class="space-y-4 sm:space-y-6 px-2 sm:px-0">
                        <h3 class="text-2xl sm:text-3xl md:text-4xl font-bold break-words leading-tight {{ $aboutBackgroundImage ? 'text-white' : 'text-gray-900' }}" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">Why Choose Us?</h3>
                        <p class="{{ $aboutBackgroundImage ? 'text-white' : 'text-gray-700' }} text-base sm:text-lg md:text-xl leading-relaxed break-words" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                            {!! render_content($aboutDescription) !!}
                        </p>
                        @if($cmsAbout && $cmsAbout->link)
                            <a href="{{ $cmsAbout->link }}" 
                               class="inline-block mt-4 px-6 sm:px-8 py-2.5 sm:py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition duration-200 shadow-lg hover:shadow-xl text-sm sm:text-base">
                                {{ $cmsAbout->link_text ?? 'Learn More' }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($aboutDescription)
            <div class="max-w-3xl mx-auto mb-8 sm:mb-10 md:mb-12 px-2 sm:px-0">
                <p class="{{ $aboutBackgroundImage ? 'text-white' : 'text-gray-700' }} text-base sm:text-lg md:text-xl leading-relaxed text-center break-words" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                    {!! render_content($aboutDescription) !!}
                </p>
            </div>
        @endif
        
        {{-- Features Grid --}}
        @if(!empty($features))
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 sm:gap-8">
                @foreach($features as $feature)
                    <div class="bg-white rounded-xl p-6 sm:p-8 shadow-lg hover:shadow-xl transition-shadow duration-300 text-center">
                        @if(isset($feature['image']) && $feature['image'])
                            <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] ?? 'Feature' }}" 
                                 class="h-16 w-16 sm:h-20 sm:w-20 mx-auto mb-4 sm:mb-6 rounded-full object-cover ring-4 ring-blue-100">
                        @else
                            <div class="text-4xl sm:text-5xl mb-4 sm:mb-6">{{ $feature['icon'] ?? '💪' }}</div>
                        @endif
                        <h3 class="text-lg sm:text-xl md:text-2xl font-bold mb-2 sm:mb-3 text-gray-900 break-words leading-tight" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{{ $feature['title'] ?? 'Feature' }}</h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed break-words" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">{!! render_content($feature['description'] ?? '') !!}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

