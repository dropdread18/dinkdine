@extends('layouts.app', ['title' => 'Check-in'])

@section('content')
    <x-page-header title="Check-in" />

    <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Today's Bookings</h2>

    @if ($bookings->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8 mb-8">No bookings today.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white mb-8">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Customer</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Checked In</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}-
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-900 font-medium">{{ $booking->user->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->status->label() }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->checked_in_at?->format('g:i A') ?: '—' }}</td>
                            <td class="py-3 pr-4 text-right space-x-3 whitespace-nowrap">
                                @if (in_array($booking->status, [\App\Enums\BookingStatus::Pending, \App\Enums\BookingStatus::Confirmed], true))
                                    @unless ($booking->checked_in_at)
                                        <form method="POST" action="{{ route('manage.checkin.bookings.check-in', $booking) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Check In</button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('manage.checkin.bookings.complete', $booking) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-emerald-600 hover:text-emerald-700 underline underline-offset-2">Complete</button>
                                    </form>
                                    <form method="POST" action="{{ route('manage.checkin.bookings.no-show', $booking) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">No-Show</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Court Status</h2>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-slate-50">
                    <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Court</th>
                    <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                    <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($courts as $court)
                    <tr class="border-t border-slate-100">
                        <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $court->name }}</td>
                        <td class="py-3 pr-4">
                            <x-badge :color="$court->status === \App\Enums\CourtStatus::Active ? 'green' : 'slate'">{{ $court->status->label() }}</x-badge>
                        </td>
                        <td class="py-3 pr-4">
                            <form method="POST" action="{{ route('manage.checkin.courts.update-status', $court) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500" onchange="this.form.requestSubmit()">
                                    @foreach (\App\Enums\CourtStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($court->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
