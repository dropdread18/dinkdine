@extends('layouts.app', ['title' => 'Courts'])

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Courts</h1>
        <a href="{{ route('admin.courts.create') }}" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">
            New Court
        </a>
    </div>

    @if ($courts->isEmpty())
        <p class="text-gray-500">No courts yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">#</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Name</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Rate</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courts as $court)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-600">{{ $court->court_number }}</td>
                            <td class="py-2 pr-4 text-gray-900">{{ $court->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">₱{{ number_format($court->hourly_rate, 2) }}</td>
                            <td class="py-2 pr-4">
                                <span @class([
                                    'inline-block rounded px-2 py-0.5 text-xs',
                                    'bg-green-100 text-green-800' => $court->status === \App\Enums\CourtStatus::Active,
                                    'bg-gray-100 text-gray-600' => $court->status !== \App\Enums\CourtStatus::Active,
                                ])>{{ $court->status->label() }}</span>
                            </td>
                            <td class="py-2 text-right space-x-2">
                                <a href="{{ route('admin.courts.edit', $court) }}" class="text-gray-700 underline">Edit</a>
                                <form method="POST" action="{{ route('admin.courts.destroy', $court) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-700 underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
