{{-- Testimonials Section --}}
@php
    $cmsTestimonialsSection = $cmsTestimonialsSection ?? null;
    $cmsTestimonials = $cmsTestimonials ?? collect();
    $testimonialsTitle = $cmsTestimonialsSection->title ?? 'What Our Members Say';
    $testimonialsDescription = $cmsTestimonialsSection->description ?? $cmsTestimonialsSection->content ?? 'Read testimonials from our satisfied members';
    $testimonialsBackgroundImage = ($cmsTestimonialsSection && $cmsTestimonialsSection->background_image) 
        ? \Illuminate\Support\Facades\Storage::url($cmsTestimonialsSection->background_image) 
        : null;
@endphp
@if($cmsTestimonials->isNotEmpty())
@php
    $testimonialsBgStyle = $testimonialsBackgroundImage 
        ? "background-image: url('{$testimonialsBackgroundImage}'); background-size: cover; background-position: center; background-attachment: fixed;"
        : '';
@endphp
<section id="testimonials" class="py-20 {{ $testimonialsBackgroundImage ? 'relative' : 'bg-gray-50' }}" style="{{ $testimonialsBgStyle }}">
    @if($testimonialsBackgroundImage)
        <div class="absolute inset-0 bg-black bg-opacity-40 z-0"></div>
    @endif
    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4 {{ $testimonialsBackgroundImage ? 'text-white' : 'text-gray-900' }}">{{ $testimonialsTitle }}</h2>
            <p class="{{ $testimonialsBackgroundImage ? 'text-white' : 'text-gray-600' }} max-w-2xl mx-auto">
                {{ $testimonialsDescription }}
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($cmsTestimonials as $testimonial)
                <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                    @if($testimonial->image)
                        <div class="flex items-center mb-4">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($testimonial->image) }}" 
                                 alt="{{ $testimonial->title }}" 
                                 class="h-16 w-16 rounded-full object-cover mr-4">
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $testimonial->title }}</h4>
                                @if($testimonial->extra_data && isset($testimonial->extra_data['position']))
                                    <p class="text-sm text-gray-500">{{ $testimonial->extra_data['position'] }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <h4 class="font-semibold text-gray-900 mb-2">{{ $testimonial->title }}</h4>
                    @endif
                    <p class="text-gray-600 italic mb-4">
                        "{{ $testimonial->content ?? $testimonial->description ?? '' }}"
                    </p>
                    @if($testimonial->extra_data && isset($testimonial->extra_data['rating']))
                        <div class="flex items-center">
                            @for($i = 0; $i < 5; $i++)
                                <svg class="w-5 h-5 {{ $i < $testimonial->extra_data['rating'] ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

