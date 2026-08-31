@php $paymentMethod = $paymentMethod ?? null; @endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
    <p class="text-xs text-slate-500 mb-1">e.g. GCash, Maya, GoTyme, Bank Transfer.</p>
    <input id="name" name="name" type="text" value="{{ old('name', $paymentMethod?->name) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="qr_code" class="block text-sm font-medium text-slate-700">QR Code{{ $paymentMethod ? ' (optional)' : '' }}</label>
    <p class="text-xs text-slate-500 mb-1">Shown on the payment screen so customers can scan to pay.</p>
    @if ($paymentMethod?->qr_code_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($paymentMethod->qr_code_path) }}" alt="{{ $paymentMethod->name }} QR code" class="w-24 h-24 object-contain rounded-lg mt-1 mb-2 border border-slate-200 bg-white p-1">
    @endif
    <input id="qr_code" name="qr_code" type="file" accept="image/*" {{ $paymentMethod ? '' : 'required' }}
           class="mt-1 block w-full text-sm text-slate-500 cursor-pointer file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-accent file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-white file:shadow-sm file:transition-colors hover:file:bg-[#7E1519]">
</div>

<div>
    <label for="sort_order" class="block text-sm font-medium text-slate-700">Sort Order (optional)</label>
    <p class="text-xs text-slate-500 mb-1">Lower numbers show first on the payment screen.</p>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $paymentMethod?->sort_order ?? 0) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<label class="flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
           @checked(old('is_active', $paymentMethod?->is_active ?? true))>
    Active (shown to customers on the payment screen)
</label>
