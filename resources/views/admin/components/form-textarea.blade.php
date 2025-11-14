@php
    $name = $name ?? '';
    $label = $label ?? '';
    $value = $value ?? null;
    $required = $required ?? false;
    $placeholder = $placeholder ?? null;
    $help = $help ?? null;
    $rows = $rows ?? 3;
    $colspan = $colspan ?? 2;
    $attributes = $attributes ?? [];
@endphp

<div class="md:col-span-{{ $colspan }}">
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <textarea 
        name="{{ $name }}" 
        id="{{ $name }}" 
        rows="{{ $rows }}"
        @if($required) required @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm 
               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
               transition duration-200 ease-in-out
               @error($name) border-red-500 focus:ring-red-500 @enderror
               placeholder-gray-400 text-gray-900 bg-white resize-none"
        @foreach($attributes as $key => $val)
            {{ $key }}="{{ $val }}"
        @endforeach
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

