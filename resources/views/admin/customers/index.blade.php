@extends('layouts.app', ['title' => 'Customers'])

@section('content')
    <x-page-header title="Customers">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.customers.create') }}">New Customer</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name, email, or phone"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-blue-500 focus:ring-blue-500">
        <x-button type="submit">Search</x-button>
        <x-button tag="a" href="{{ route('admin.customers.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($customers->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No customers match this search.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Email</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Phone</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Bookings</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $customer->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $customer->email }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $customer->phone ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $customer->bookings_count }}</td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$customer->is_active ? 'green' : 'slate'">{{ $customer->is_active ? 'Active' : 'Disabled' }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
@endsection
