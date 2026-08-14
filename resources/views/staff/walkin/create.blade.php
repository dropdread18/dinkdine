@extends('layouts.app', ['title' => 'Walk-in Booking Details'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">New Walk-in Booking</h1>

    <div class="bg-white border rounded-lg p-4 max-w-md space-y-2 text-sm mb-6">
        <div class="flex justify-between"><span class="text-gray-500">Court</span><span class="text-gray-900">{{ $court->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Date</span><span class="text-gray-900">{{ \Illuminate\Support\Carbon::parse($date)->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-gray-500">Time</span>
            <span class="text-gray-900">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $startTime)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $endTime)->format('g:i A') }}
            </span>
        </div>
    </div>

    <form method="GET" action="{{ route('manage.walkin.create', $court) }}" class="max-w-md flex gap-2 mb-4">
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="start_time" value="{{ $startTime }}">
        <input type="hidden" name="end_time" value="{{ $endTime }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search existing customer by name, email, or phone"
               class="flex-1 rounded border-gray-300 shadow-sm text-sm">
        <button type="submit" class="bg-gray-200 rounded px-3 py-2 text-sm">Search</button>
    </form>

    <form method="POST" action="{{ route('manage.walkin.store', $court) }}" class="max-w-md space-y-4">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="start_time" value="{{ $startTime }}">
        <input type="hidden" name="end_time" value="{{ $endTime }}">

        @if ($existingCustomers->isNotEmpty())
            <div>
                <p class="block text-sm font-medium text-gray-700 mb-1">Matching customers</p>
                <div class="space-y-1">
                    @foreach ($existingCustomers as $customer)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="existing_user_id" value="{{ $customer->id }}">
                            {{ $customer->name }} — {{ $customer->email }} @if ($customer->phone) ({{ $customer->phone }}) @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @elseif ($q)
            <p class="text-sm text-gray-500">No matches for "{{ $q }}". Create a new customer below.</p>
        @endif

        <fieldset class="border rounded p-3 space-y-3">
            <legend class="text-sm font-medium text-gray-700 px-1">Or create a new customer</legend>

            <div>
                <label for="new_customer_name" class="block text-sm font-medium text-gray-700">Name</label>
                <input id="new_customer_name" name="new_customer_name" type="text" value="{{ old('new_customer_name') }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="new_customer_email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="new_customer_email" name="new_customer_email" type="email" value="{{ old('new_customer_email') }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="new_customer_phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                <input id="new_customer_phone" name="new_customer_phone" type="text" value="{{ old('new_customer_phone') }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>
        </fieldset>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Create Booking
        </button>

        <a href="{{ route('manage.walkin.index', ['date' => $date]) }}" class="block text-center text-sm text-gray-600 underline">
            Choose a different time
        </a>
    </form>
@endsection
