@extends('layouts.app', ['title' => 'Courts'])

@section('content')
    <x-page-header title="Courts">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.courts.create') }}">New Court</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($courts->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No courts yet.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">#</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Rate</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courts as $court)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-500">{{ $court->court_number }}</td>
                            <td class="py-3 pr-4 text-slate-900 font-medium">{{ $court->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">₱{{ number_format($court->hourly_rate, 2) }}</td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$court->status === \App\Enums\CourtStatus::Active ? 'green' : 'slate'">{{ $court->status->label() }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 text-right space-x-3">
                                <a href="{{ route('admin.courts.edit', $court) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.courts.destroy', $court) }}" class="inline">
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
