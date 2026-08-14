@extends('layouts.app', ['title' => 'Staff Dashboard'])

@section('content')
    <x-page-header title="Today's Bookings">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('manage.walkin.index') }}">New Walk-in Booking</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($todaysBookings->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No bookings today.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Customer</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todaysBookings as $booking)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} -
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-900 font-medium">{{ $booking->user->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->status->label() }}</td>
                            <td class="py-3 pr-4"><a href="{{ route('bookings.show', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <a href="{{ route('manage.bookings.index') }}" class="inline-block mt-6 text-sm text-teal-600 hover:text-teal-700 underline underline-offset-2">View all bookings</a>
@endsection
