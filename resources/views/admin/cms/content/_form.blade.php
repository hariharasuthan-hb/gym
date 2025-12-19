@php
    $content = $content ?? null;
    $isEdit = $isEdit ?? false;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Left Column --}}
    <div class="space-y-6">
        {{-- Title --}}
        @include('admin.components.form-input', [
            'name' => 'title',
            'label' => 'Title',
            'value' => old('title', $content->title ?? null),
            'required' => true,
        ])

        {{-- Key --}}
        @include('admin.components.form-input', [
            'name' => 'key',
            'label' => 'Key',
            'value' => old('key', $content->key ?? null),
            'required' => true,
            'placeholder' => 'unique-content-key',
            'help' => 'Unique identifier (e.g., hero-banner, about-section)',
            'attributes' => ['class' => 'font-mono'],
        ])

        {{-- Type --}}
        @include('admin.components.form-select', [
            'name' => 'type',
            'label' => 'Type',
            'options' => [
                'hero' => 'Hero',
                'about' => 'About',
                'services' => 'Services',
                'bmi-calculator' => 'BMI Calculator',
                'testimonials' => 'Testimonials',
                'features' => 'Features',
                'cta' => 'Call to Action',
                'other' => 'Other',
            ],
            'value' => old('type', $content->type ?? null),
            'required' => true,
            'placeholder' => 'Select Type',
        ])

        {{-- Order --}}
        @include('admin.components.form-input', [
            'name' => 'order',
            'label' => 'Order',
            'type' => 'number',
            'value' => old('order', $content->order ?? 0),
            'attributes' => ['min' => '0'],
        ])

        {{-- Current Image --}}
        @if($isEdit && $content->image)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Image
            </label>
            <img src="{{ \Illuminate\Support\Facades\Storage::url($content->image) }}" alt="{{ $content->title }}" class="h-32 w-full object-cover rounded-lg border border-gray-300">
            <div class="mt-2 flex items-center">
                <input type="checkbox" name="remove_image" id="remove_image" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                <label for="remove_image" class="ml-2 text-sm text-red-600 font-medium">Remove current image</label>
            </div>
        </div>
        @endif

        {{-- Image --}}
        <div>
            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                {{ ($isEdit && $content->image) ? 'Replace Image' : 'Image' }}
            </label>
            <input type="file" 
                   name="image" 
                   id="image" 
                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP</p>
        </div>

        {{-- Current Background Image --}}
        @if($isEdit && $content->background_image)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Background Image
            </label>
            <img src="{{ \Illuminate\Support\Facades\Storage::url($content->background_image) }}" alt="{{ $content->title }} Background" class="h-32 w-full object-cover rounded-lg border border-gray-300">
            <div class="mt-2 flex items-center">
                <input type="checkbox" name="remove_background_image" id="remove_background_image" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                <label for="remove_background_image" class="ml-2 text-sm text-red-600 font-medium">Remove current background image</label>
            </div>
        </div>
        @endif

        {{-- Background Image --}}
        <div>
            <label for="background_image" class="block text-sm font-medium text-gray-700 mb-2">
                {{ ($isEdit && $content->background_image) ? 'Replace Background Image' : 'Background Image' }}
            </label>
            <input type="file" 
                   name="background_image" 
                   id="background_image" 
                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Max size: 5MB. Formats: JPEG, PNG, JPG, GIF, WEBP. Used as background for content sections.</p>
        </div>

        {{-- Current Background Video --}}
        @if($isEdit && $content->background_video)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Background Video
            </label>
            <video controls class="w-full rounded-lg border border-gray-300 mb-2">
                <source src="{{ \Illuminate\Support\Facades\Storage::url($content->background_video) }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="mt-2 flex items-center">
                <input type="checkbox" name="remove_background_video" id="remove_background_video" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                <label for="remove_background_video" class="ml-2 text-sm text-red-600 font-medium">Remove current background video</label>
            </div>
        </div>
        @endif

        {{-- Background Video --}}
        <div>
            <label for="background_video" class="block text-sm font-medium text-gray-700 mb-2">
                {{ ($isEdit && $content->background_video) ? 'Replace Background Video' : 'Background Video' }}
            </label>
            <input type="file" 
                   name="background_video" 
                   id="background_video" 
                   accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Max size: 50MB. Formats: MP4, MOV, AVI, WMV. Used as background video for content sections. Note: Background video takes priority over background image.</p>
        </div>

        {{-- Current Video --}}
        @if($isEdit && $content->video_path)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Current Video
            </label>
            <video controls class="w-full rounded-lg border border-gray-300 mb-2">
                <source src="{{ \Illuminate\Support\Facades\Storage::url($content->video_path) }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="mt-2 flex items-center">
                <input type="checkbox" name="remove_video" id="remove_video" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                <label for="remove_video" class="ml-2 text-sm text-red-600 font-medium">Remove current video</label>
            </div>
        </div>
        @endif

        {{-- Video Upload --}}
        <div>
            <label for="video" class="block text-sm font-medium text-gray-700 mb-2">
                {{ ($isEdit && $content->video_path) ? 'Replace Video' : 'Video (optional)' }}
            </label>
            <input type="file" 
                   name="video" 
                   id="video" 
                   accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Max size: 50MB. Formats: MP4, MOV, AVI, WMV.</p>
        </div>

        {{-- Background Video Toggle --}}
        <div class="flex items-center">
            <input type="checkbox"
                   name="video_is_background"
                   id="video_is_background"
                   value="1"
                   {{ old('video_is_background', $content->video_is_background ?? false) ? 'checked' : '' }}
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="video_is_background" class="ml-2 block text-sm text-gray-700">
                Set as background video for homepage section
            </label>
        </div>

        {{-- Title Color --}}
        <div>
            <label for="title_color" class="block text-sm font-medium text-gray-700 mb-2">
                Title Color
            </label>
            <input type="color"
                   name="title_color"
                   id="title_color"
                   value="{{ old('title_color', $content->title_color ?? '#ffffff') }}"
                   class="h-10 w-20 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Color for the title text</p>
        </div>

        {{-- Description Color --}}
        <div>
            <label for="description_color" class="block text-sm font-medium text-gray-700 mb-2">
                Description Color
            </label>
            <input type="color"
                   name="description_color"
                   id="description_color"
                   value="{{ old('description_color', $content->description_color ?? '#ffffff') }}"
                   class="h-10 w-20 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Color for the description text</p>
        </div>

        {{-- Content Color --}}
        <div>
            <label for="content_color" class="block text-sm font-medium text-gray-700 mb-2">
                Content Color
            </label>
            <input type="color"
                   name="content_color"
                   id="content_color"
                   value="{{ old('content_color', $content->content_color ?? '#ffffff') }}"
                   class="h-10 w-20 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Color for the content text</p>
        </div>

        {{-- Link --}}
        @include('admin.components.form-input', [
            'name' => 'link',
            'label' => 'Link URL',
            'type' => 'url',
            'value' => old('link', $content->link ?? null),
            'placeholder' => 'https://example.com',
        ])

        {{-- Link Text --}}
        @include('admin.components.form-input', [
            'name' => 'link_text',
            'label' => 'Link Text',
            'value' => old('link_text', $content->link_text ?? null),
            'placeholder' => 'Learn More',
        ])

        {{-- Status --}}
        <div class="flex items-center">
            <input type="checkbox" 
                   name="is_active" 
                   id="is_active" 
                   value="1"
                   {{ old('is_active', $content->is_active ?? ($isEdit ? false : true)) ? 'checked' : '' }}
                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="is_active" class="ml-2 block text-sm text-gray-700">
                Active
            </label>
        </div>
    </div>

    {{-- Right Column --}}
    <div class="space-y-6">
        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                Description
            </label>
            <textarea name="description" 
                      id="description" 
                      rows="4"
                      maxlength="1000"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description', $content->description ?? null) }}</textarea>
            <p class="mt-1 text-sm text-gray-500">Brief description (max 1000 characters)</p>
        </div>

        {{-- Content --}}
        <div>
            @include('admin.components.rich-text-editor', [
                'name' => 'content',
                'label' => 'Content',
                'value' => old('content', $content->content ?? null),
                'height' => 500,
                'toolbar' => 'full',
                'help' => 'Main content text (supports HTML formatting)',
            ])
        </div>
    </div>
</div>

