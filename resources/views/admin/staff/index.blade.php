@extends('layouts.app', ['title' => 'Staff'])

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Staff</h1>
        <a href="{{ route('admin.staff.create') }}" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">
            New Staff Account
        </a>
    </div>

    @if ($staff->isEmpty())
        <p class="text-gray-500">No staff accounts yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Name</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Email</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Phone</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $member)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-900">{{ $member->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $member->email }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $member->phone ?: '—' }}</td>
                            <td class="py-2 pr-4">
                                @if ($member->is_active)
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-gray-100 text-gray-600">Disabled</span>
                                @endif
                            </td>
                            <td class="py-2 text-right space-x-2">
                                <a href="{{ route('admin.staff.edit', $member) }}" class="text-gray-700 underline">Edit</a>
                                <form method="POST" action="{{ route('admin.staff.toggle-active', $member) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if ($member->is_active)
                                        <button type="submit" class="text-red-700 underline">Disable</button>
                                    @else
                                        <button type="submit" class="text-green-700 underline">Enable</button>
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
