@extends('layouts.app', ['title' => 'Bookings'])

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Bookings</h1>
        <a href="{{ route('manage.walkin.index') }}" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">
            New Walk-in Booking
        </a>
    </div>

    <form method="GET" action="{{ route('manage.bookings.index') }}" class="flex flex-wrap gap-2 mb-4 text-sm">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, email, or booking #"
               class="rounded border-gray-300 shadow-sm w-64">

        <input type="date" name="date" value="{{ request('date') }}" class="rounded border-gray-300 shadow-sm">

        <select name="court_id" class="rounded border-gray-300 shadow-sm">
            <option value="">All Courts</option>
            @foreach ($courts as $court)
                <option value="{{ $court->id }}" @selected(request('court_id') == $court->id)>{{ $court->name }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded border-gray-300 shadow-sm">
            <option value="">Any Status</option>
            @foreach (\App\Enums\BookingStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <select name="payment_status" class="rounded border-gray-300 shadow-sm">
            <option value="">Any Payment</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $paymentStatus)
                <option value="{{ $paymentStatus->value }}" @selected(request('payment_status') === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
            @endforeach
        </select>

        <select name="source" class="rounded border-gray-300 shadow-sm">
            <option value="">Any Source</option>
            @foreach (\App\Enums\BookingSource::cases() as $source)
                <option value="{{ $source->value }}" @selected(request('source') === $source->value)>{{ $source->label() }}</option>
            @endforeach
        </select>

        <button type="submit" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">Filter</button>
        <a href="{{ route('manage.bookings.index') }}" class="text-gray-600 underline self-center">Clear</a>
    </form>

    @if ($bookings->isEmpty())
        <p class="text-gray-500">No bookings match these filters.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">#</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Customer</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Date / Time</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Payment</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Source</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-600">PB-{{ $booking->id }}</td>
                            <td class="py-2 pr-4 text-gray-900">{{ $booking->user->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->court->name }}</td>
                            <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">
                                {{ $booking->booking_date->format('M j, Y') }},
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}
                            </td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->status->label() }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->payment_status->label() }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->source->label() }}</td>
                            <td class="py-2 space-x-2 whitespace-nowrap">
                                <a href="{{ route('bookings.show', $booking) }}" class="text-gray-700 underline">View</a>
                                @if ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                                    <a href="{{ route('manage.bookings.reschedule', $booking) }}" class="text-gray-700 underline">Reschedule</a>
                                    <form method="POST" action="{{ route('manage.bookings.cancel', $booking) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-700 underline">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $bookings->links() }}</div>
    @endif
@endsection
