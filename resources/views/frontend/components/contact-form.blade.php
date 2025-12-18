{{-- Contact Section --}}
<section id="contact" class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gray-50">
    <div class="container mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="text-center mb-8 sm:mb-10 md:mb-12">
            <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-3 sm:mb-4 break-words leading-tight px-2 sm:px-0" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">Contact Us</h2>
            <p class="text-gray-600 max-w-2xl mx-auto text-sm sm:text-base md:text-lg px-2 sm:px-0 break-words leading-relaxed" style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                Have questions? Get in touch with us today!
            </p>
        </div>
        <div class="max-w-2xl mx-auto px-2 sm:px-0">
            <form action="{{ route('frontend.contact.store') }}" method="POST" class="bg-white rounded-lg shadow-lg p-6 sm:p-8">
                @csrf
                
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 font-semibold mb-2">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           required>
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           required>
                </div>

                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 font-semibold mb-2">Phone (Optional)</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="mb-4">
                    <label for="message" class="block text-gray-700 font-semibold mb-2">Message</label>
                    <textarea name="message" id="message" rows="5" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              required>{{ old('message') }}</textarea>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2.5 sm:py-3 rounded-lg font-semibold hover:bg-blue-700 transition text-sm sm:text-base">
                    Send Message
                </button>
            </form>
        </div>
    </div>
</section>

