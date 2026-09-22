@extends('layouts.app', ['title' => 'Walk-in Booking Details'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">New Walk-in Booking</h1>

    <x-card class="max-w-md space-y-2 text-sm mb-6">
        <p class="text-xs font-medium text-slate-500 uppercase mb-1">{{ count($slots) }} slot{{ count($slots) === 1 ? '' : 's' }} selected</p>
        <div class="space-y-1.5">
            @foreach ($slots as $key => $slot)
                <div class="flex justify-between">
                    <span class="text-slate-900">
                        {{ $slot['court']->name }} · {{ \Illuminate\Support\Carbon::parse($slot['date'])->format('M j') }},
                        {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['start_time'])->format('g:i A') }} -
                        {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['end_time'])->format('g:i A') }}
                    </span>
                    <span class="text-slate-500">₱{{ number_format($slotPrices[$key], 2) }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between border-t border-slate-100 pt-2 mt-2 font-semibold">
            <span class="text-slate-700">Total</span>
            <span class="text-slate-900">₱{{ number_format($totalPrice, 2) }}</span>
        </div>
    </x-card>

    <form method="POST" action="{{ route('manage.walkin.store') }}" class="max-w-md space-y-4">
        @csrf
        @foreach ($rawSlots as $rawSlot)
            <input type="hidden" name="slots[]" value="{{ $rawSlot }}">
        @endforeach

        <div>
            <label for="customer_name" class="block text-sm font-medium text-slate-700">Customer name</label>
            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" autofocus
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
        </div>

        <x-button type="submit" class="w-full">Create Booking{{ count($slots) === 1 ? '' : 's' }}</x-button>

        <a href="{{ route('manage.walkin.index', ['date' => $slots[0]['date'] ?? now()->toDateString()]) }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Choose different times
        </a>
    </form>
@endsection
