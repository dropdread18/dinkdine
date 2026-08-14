@extends('layouts.app', ['title' => $customer->name])

@section('content')
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">{{ $customer->name }}</h1>
        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer) }}">
            @csrf
            @method('PATCH')
            @if ($customer->is_active)
                <x-button type="submit" variant="danger">Disable Account</x-button>
            @else
                <x-button type="submit" variant="secondary">Enable Account</x-button>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <x-card>
            <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Profile</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="text-slate-900 font-medium">{{ $customer->email }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd class="text-slate-900 font-medium">{{ $customer->phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Registered</dt><dd class="text-slate-900 font-medium">{{ $customer->created_at->format('M j, Y') }}</dd></div>
                <div class="flex justify-between items-center">
                    <dt class="text-slate-500">Status</dt>
                    <dd><x-badge :color="$customer->is_active ? 'green' : 'slate'">{{ $customer->is_active ? 'Active' : 'Disabled' }}</x-badge></dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Booking History</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-500">Total Bookings</dt><dd class="text-slate-900 font-medium">{{ $totalBookings }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Completed</dt><dd class="text-slate-900 font-medium">{{ $completedBookings }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Cancelled</dt><dd class="text-slate-900 font-medium">{{ $cancelledBookings }}</dd></div>
                <div class="flex justify-between pt-2 border-t border-slate-100"><dt class="text-slate-500">Total Spent</dt><dd class="text-slate-900 font-semibold">₱{{ number_format($totalSpent, 2) }}</dd></div>
            </dl>
        </x-card>
    </div>

    <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Recent Bookings</h2>
    @if ($recentBookings->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No bookings yet.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Date</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentBookings as $booking)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $booking->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->booking_date->toDateString() }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->start_time }}–{{ $booking->end_time }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $booking->status->label() }}</td>
                            <td class="py-3 pr-4"><a href="{{ route('bookings.show', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <a href="{{ route('admin.customers.index') }}" class="block mt-6 text-sm text-teal-600 hover:text-teal-700 underline underline-offset-2">Back to Customers</a>
@endsection
