@extends('layouts.app', ['title' => 'Payment Methods'])

@section('content')
    <x-page-header title="Payment Methods">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.payment-methods.create') }}">New Payment Method</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($paymentMethods->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No payment methods yet - customers won't see a QR code to scan on the payment screen until you add one.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">QR Code</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paymentMethods as $method)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($method->qr_code_path) }}" alt="{{ $method->name }} QR code" class="w-12 h-12 object-contain rounded-lg border border-slate-200 bg-white p-1">
                            </td>
                            <td class="py-3 pr-4 text-slate-900 font-medium">{{ $method->name }}</td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$method->is_active ? 'green' : 'slate'">{{ $method->is_active ? 'Active' : 'Inactive' }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 text-right space-x-3">
                                <a href="{{ route('admin.payment-methods.edit', $method) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
