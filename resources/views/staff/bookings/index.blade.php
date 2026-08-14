@extends('layouts.app', ['title' => 'Bookings'])

@section('content')
    <x-page-header title="Bookings">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('manage.walkin.index') }}">New Walk-in Booking</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('manage.bookings.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, email, or booking #"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-teal-500 focus:ring-teal-500">

        <input type="date" name="date" value="{{ request('date') }}" class="rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">

        <select name="court_id" class="rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">All Courts</option>
            @foreach ($courts as $court)
                <option value="{{ $court->id }}" @selected(request('court_id') == $court->id)>{{ $court->name }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Any Status</option>
            @foreach (\App\Enums\BookingStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <select name="payment_status" class="rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Any Payment</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $paymentStatus)
                <option value="{{ $paymentStatus->value }}" @selected(request('payment_status') === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
            @endforeach
        </select>

        <select name="source" class="rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Any Source</option>
            @foreach (\App\Enums\BookingSource::cases() as $source)
                <option value="{{ $source->value }}" @selected(request('source') === $source->value)>{{ $source->label() }}</option>
            @endforeach
        </select>

        <x-button type="submit">Filter</x-button>
        <x-button tag="a" href="{{ route('manage.bookings.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($bookings->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No bookings match these filters.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">#</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Customer</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Date / Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Payment</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Source</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-500">PB-{{ $booking->id }}</td>
                            <td class="py-3 pr-4 text-slate-900 font-medium">{{ $booking->user->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-600 whitespace-nowrap">
                                {{ $booking->booking_date->format('M j, Y') }},
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->status->label() }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->payment_status->label() }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->source->label() }}</td>
                            <td class="py-3 pr-4 space-x-3 whitespace-nowrap">
                                <a href="{{ route('bookings.show', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">View</a>
                                @if ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                                    <a href="{{ route('manage.bookings.reschedule', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Reschedule</a>
                                    <form method="POST" action="{{ route('manage.bookings.cancel', $booking) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Cancel</button>
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
