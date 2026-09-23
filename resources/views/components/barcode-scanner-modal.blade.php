{{-- A camera-based barcode scanner button + modal, backed by
     window.BarcodeScanner (resources/js/barcode-scanner.js, an
     html5-qrcode wrapper) - the page that includes this must also load
     that script via @vite(['resources/js/barcode-scanner.js']).

     Two modes:
     - Pass `fill-target` (an input element's id) to have a scanned code
       fill that field and auto-close the modal - used on the product
       form to scan a barcode straight off the packaging instead of
       typing it.
     - Omit `fill-target` to instead dispatch a window "barcode-scanned"
       CustomEvent ({ detail: { text } }) on every scan and stay open -
       used on the POS grid, where each scan should add one unit to the
       cart and the camera should stay ready for the next item. --}}
@props([
    'id' => 'barcode-reader-'.\Illuminate\Support\Str::random(8),
    'label' => 'Scan Barcode',
    'fillTarget' => null,
    // With fill-target: also submit that field's form right after filling
    // it - used where a scan should immediately run a search.
    'submitOnScan' => false,
    // 'link' (default) matches a plain text-link trigger elsewhere in the
    // app; 'button' matches x-button's secondary variant, for a trigger
    // that needs to read as its own page action (e.g. the POS toolbar).
    'variant' => 'link',
])

@php
    $triggerClasses = match ($variant) {
        'button' => 'rounded-lg px-4 py-2.5 bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 font-medium shadow-sm',
        default => 'text-sm font-medium text-blue-600 hover:text-blue-700',
    };
@endphp

<div x-data="{
        scannerOpen: false,
        scannerError: '',
        start() {
            this.scannerOpen = true;
            this.scannerError = '';
            this.$nextTick(() => window.BarcodeScanner.start('{{ $id }}', (text, err) => {
                if (err) {
                    this.scannerError = 'Could not access the camera. Check permissions and try again.';
                    return;
                }
                @if ($fillTarget)
                    const el = document.getElementById('{{ $fillTarget }}');
                    if (el) {
                        el.value = text;
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        @if ($submitOnScan)
                            el.form?.requestSubmit();
                        @endif
                    }
                    this.close();
                @else
                    window.dispatchEvent(new CustomEvent('barcode-scanned', { detail: { text } }));
                @endif
            }));
        },
        close() {
            this.scannerOpen = false;
            window.BarcodeScanner.stop('{{ $id }}');
        },
    }">
    <button type="button" @click="start()"
            {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 '.$triggerClasses]) }}>
        {{ $label }}
    </button>

    <div x-show="scannerOpen" x-cloak @keydown.escape.window="close()"
         class="fixed inset-0 z-50 flex items-center justify-center p-6" style="background: rgba(15, 23, 42, 0.6);">
        <div @click="close()" class="absolute inset-0"></div>
        <div @click.stop class="relative rounded-2xl p-4 max-w-sm w-full bg-white space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-slate-900">Scan Barcode</span>
                <button type="button" @click="close()" class="text-sm text-slate-500 hover:text-slate-900">Close</button>
            </div>
            <div id="{{ $id }}" class="rounded-lg overflow-hidden bg-slate-900" style="min-height: 220px;"></div>
            <p class="text-xs" :class="scannerError ? 'text-red-600' : 'text-slate-500'" x-text="scannerError || 'Point the camera at the barcode.'"></p>
        </div>
    </div>
</div>
