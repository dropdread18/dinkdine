@extends('layouts.app', ['title' => 'Check-in'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Check-in</h1>

    <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Today's Bookings</h2>

    @if ($bookings->isEmpty())
        <p class="text-gray-500 text-sm mb-8">No bookings today.</p>
    @else
        <div class="overflow-x-auto mb-8">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Time</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Customer</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Checked In</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}-
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                            </td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->court->name }}</td>
                            <td class="py-2 pr-4 text-gray-900">{{ $booking->user->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->status->label() }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->checked_in_at?->format('g:i A') ?: '—' }}</td>
                            <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                @if (in_array($booking->status, [\App\Enums\BookingStatus::Pending, \App\Enums\BookingStatus::Confirmed], true))
                                    @unless ($booking->checked_in_at)
                                        <form method="POST" action="{{ route('manage.checkin.bookings.check-in', $booking) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-gray-700 underline">Check In</button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('manage.checkin.bookings.complete', $booking) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-700 underline">Complete</button>
                                    </form>
                                    <form method="POST" action="{{ route('manage.checkin.bookings.no-show', $booking) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-700 underline">No-Show</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Court Status</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                    <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                    <th class="text-left font-medium text-gray-500 pb-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($courts as $court)
                    <tr class="border-t">
                        <td class="py-2 pr-4 text-gray-900">{{ $court->name }}</td>
                        <td class="py-2 pr-4">
                            <span @class([
                                'inline-block rounded px-2 py-0.5 text-xs',
                                'bg-green-100 text-green-800' => $court->status === \App\Enums\CourtStatus::Active,
                                'bg-gray-100 text-gray-600' => $court->status !== \App\Enums\CourtStatus::Active,
                            ])>{{ $court->status->label() }}</span>
                        </td>
                        <td class="py-2">
                            <form method="POST" action="{{ route('manage.checkin.courts.update-status', $court) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="rounded border-gray-300 shadow-sm text-sm" onchange="this.form.requestSubmit()">
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
