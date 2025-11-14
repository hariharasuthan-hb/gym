{{-- Testimonials Section --}}
@php
    $cmsTestimonials = $cmsTestimonials ?? collect();
@endphp
@if($cmsTestimonials->isNotEmpty())
<section id="testimonials" class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4">What Our Members Say</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Read testimonials from our satisfied members
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

