@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label" for="category">Category<span class="text-red-500">*</span></label>
        <input type="text"
               name="category"
               id="category"
               value="{{ old('category', $income->category ?? '') }}"
               class="form-input w-full"
               required>
        @error('category')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="source">Source</label>
        <input type="text"
               name="source"
               id="source"
               value="{{ old('source', $income->source ?? '') }}"
               class="form-input w-full">
        @error('source')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="amount">Amount<span class="text-red-500">*</span></label>
        <input type="number"
               step="0.01"
               min="0"
               name="amount"
               id="amount"
               value="{{ old('amount', $income->amount ?? '') }}"
               class="form-input w-full"
               required>
        @error('amount')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="received_at">Received At<span class="text-red-500">*</span></label>
        <input type="date"
               name="received_at"
               id="received_at"
               value="{{ old('received_at', isset($income->received_at) ? $income->received_at->format('Y-m-d') : '') }}"
               class="form-input w-full"
               required>
        @error('received_at')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="payment_method">Payment Method</label>
        <input type="text"
               name="payment_method"
               id="payment_method"
               value="{{ old('payment_method', $income->payment_method ?? '') }}"
               class="form-input w-full">
        @error('payment_method')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label class="form-label" for="reference">Reference</label>
        <input type="text"
               name="reference"
               id="reference"
               value="{{ old('reference', $income->reference ?? '') }}"
               class="form-input w-full">
        @error('reference')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
    <div class="md:col-span-2">
        <label class="form-label" for="notes">Notes</label>
        <textarea name="notes"
                  id="notes"
                  rows="4"
                  class="form-input w-full">{{ old('notes', $income->notes ?? '') }}</textarea>
        @error('notes')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex gap-3">
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.incomes.index') }}" class="btn btn-secondary">
        Cancel
    </a>
</div>

