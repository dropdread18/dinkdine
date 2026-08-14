@extends('layouts.app', ['title' => $customer->name])

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ $customer->name }}</h1>
        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer) }}">
            @csrf
            @method('PATCH')
            @if ($customer->is_active)
                <button type="submit" class="border border-red-300 text-red-700 rounded px-3 py-2 text-sm font-medium">
                    Disable Account
                </button>
            @else
                <button type="submit" class="border border-green-300 text-green-700 rounded px-3 py-2 text-sm font-medium">
                    Enable Account
                </button>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white border rounded-lg p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Profile</h2>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $customer->email }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd class="text-gray-900">{{ $customer->phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Registered</dt><dd class="text-gray-900">{{ $customer->created_at->format('M j, Y') }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @if ($customer->is_active)
                            <span class="inline-block rounded px-2 py-0.5 text-xs bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-block rounded px-2 py-0.5 text-xs bg-gray-100 text-gray-600">Disabled</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-white border rounded-lg p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Booking History</h2>
            <dl class="text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Total Bookings</dt><dd class="text-gray-900">{{ $totalBookings }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Completed</dt><dd class="text-gray-900">{{ $completedBookings }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Cancelled</dt><dd class="text-gray-900">{{ $cancelledBookings }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Total Spent</dt><dd class="text-gray-900">₱{{ number_format($totalSpent, 2) }}</dd></div>
            </dl>
        </div>
    </div>

    <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Recent Bookings</h2>
    @if ($recentBookings->isEmpty())
        <p class="text-gray-500">No bookings yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Date</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Time</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentBookings as $booking)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-900">{{ $booking->court->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->booking_date->toDateString() }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->start_time }}–{{ $booking->end_time }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->status->label() }}</td>
                            <td class="py-2"><a href="{{ route('bookings.show', $booking) }}" class="text-gray-700 underline">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <a href="{{ route('admin.customers.index') }}" class="block mt-6 text-sm text-gray-600 underline">Back to Customers</a>
@endsection
