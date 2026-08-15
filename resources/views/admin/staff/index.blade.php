@extends('layouts.app', ['title' => 'Staff'])

@section('content')
    <x-page-header title="Staff">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.staff.create') }}">New Staff Account</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($staff->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No staff accounts yet.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Email</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Phone</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $member)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $member->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $member->email }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $member->phone ?: '—' }}</td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$member->is_active ? 'green' : 'slate'">{{ $member->is_active ? 'Active' : 'Disabled' }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 text-right space-x-3">
                                <a href="{{ route('admin.staff.edit', $member) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.staff.toggle-active', $member) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if ($member->is_active)
                                        <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Disable</button>
                                    @else
                                        <button type="submit" class="text-green-600 hover:text-green-700 underline underline-offset-2">Enable</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
