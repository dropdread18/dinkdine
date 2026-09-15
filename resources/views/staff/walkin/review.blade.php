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

    <form method="GET" action="{{ route('manage.walkin.review') }}" class="max-w-md flex gap-2 mb-4">
        @foreach ($rawSlots as $rawSlot)
            <input type="hidden" name="slots[]" value="{{ $rawSlot }}">
        @endforeach
        <input type="text" name="q" value="{{ $q }}" placeholder="Search existing customer by name, email, or phone"
               class="flex-1 rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
        <x-button type="submit" variant="secondary">Search</x-button>
    </form>

    <form method="POST" action="{{ route('manage.walkin.store') }}" class="max-w-md space-y-4">
        @csrf
        @foreach ($rawSlots as $rawSlot)
            <input type="hidden" name="slots[]" value="{{ $rawSlot }}">
        @endforeach

        @if ($existingCustomers->isNotEmpty())
            <div>
                <p class="block text-sm font-medium text-slate-700 mb-1">Matching customers</p>
                <div class="space-y-1">
                    @foreach ($existingCustomers as $customer)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="existing_user_id" value="{{ $customer->id }}" class="text-blue-600 focus:ring-blue-500">
                            {{ $customer->name }} — {{ $customer->email }} @if ($customer->phone) ({{ $customer->phone }}) @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @elseif ($q)
            <p class="text-sm text-slate-500">No matches for "{{ $q }}". Create a new customer below.</p>
        @endif

        <fieldset class="border border-slate-200 rounded-xl p-4 space-y-3">
            <legend class="text-sm font-medium text-slate-700 px-1">Or create a new customer</legend>

            <div>
                <label for="new_customer_name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="new_customer_name" name="new_customer_name" type="text" value="{{ old('new_customer_name') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="new_customer_email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="new_customer_email" name="new_customer_email" type="email" value="{{ old('new_customer_email') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="new_customer_phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                <input id="new_customer_phone" name="new_customer_phone" type="text" value="{{ old('new_customer_phone') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </fieldset>

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
