@extends('layouts.app', ['title' => 'Customers'])

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Customers</h1>
        <a href="{{ route('admin.customers.create') }}" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">
            New Customer
        </a>
    </div>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap gap-2 mb-4 text-sm">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name, email, or phone"
               class="rounded border-gray-300 shadow-sm w-64">
        <button type="submit" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">Search</button>
        <a href="{{ route('admin.customers.index') }}" class="text-gray-600 underline self-center">Clear</a>
    </form>

    @if ($customers->isEmpty())
        <p class="text-gray-500">No customers match this search.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Name</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Email</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Phone</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Bookings</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-900">{{ $customer->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $customer->email }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $customer->phone ?: '—' }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $customer->bookings_count }}</td>
                            <td class="py-2 pr-4">
                                @if ($customer->is_active)
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-gray-100 text-gray-600">Disabled</span>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-gray-700 underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
@endsection
