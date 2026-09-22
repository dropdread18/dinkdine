@extends('layouts.app', ['title' => 'Inventory History'])

@section('content')
    <x-page-header title="Inventory History">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.store.inventory.index') }}" variant="ghost">Back to Inventory</x-button>
        </x-slot:actions>
    </x-page-header>

    <p class="text-sm text-slate-500 mb-4">{{ $product->name }} - currently {{ $product->stock_quantity }} {{ $product->unit }}.</p>

    @if ($movements->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No stock movements recorded yet.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Date</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Type</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Change</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Previous</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">New</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Reason</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movements as $movement)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-600 whitespace-nowrap">{{ $movement->created_at->format('M j, Y g:i A') }}</td>
                            <td class="py-3 pr-4"><x-badge :color="$movement->quantity >= 0 ? 'green' : 'red'">{{ $movement->type->label() }}</x-badge></td>
                            <td class="py-3 pr-4 font-medium {{ $movement->quantity >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                {{ $movement->quantity >= 0 ? '+' : '' }}{{ $movement->quantity }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ $movement->previous_quantity }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $movement->new_quantity }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $movement->reason ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $movement->user?->name ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $movements->links() }}</div>
    @endif
@endsection
