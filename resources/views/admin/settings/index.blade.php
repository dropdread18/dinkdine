@extends('layouts.app', ['title' => 'Settings'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Settings</h1>

    <section class="mb-10">
        <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Facility &amp; Booking Rules</h2>

        <form method="POST" action="{{ route('manage.settings.update') }}" class="max-w-lg space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="facility_name" class="block text-sm font-medium text-gray-700">Facility Name</label>
                <input id="facility_name" name="facility_name" type="text" required
                       value="{{ old('facility_name', $settings['facility_name']) }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="facility_address" class="block text-sm font-medium text-gray-700">Address (optional)</label>
                <input id="facility_address" name="facility_address" type="text"
                       value="{{ old('facility_address', $settings['facility_address']) }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="facility_phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                    <input id="facility_phone" name="facility_phone" type="text"
                           value="{{ old('facility_phone', $settings['facility_phone']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="facility_email" class="block text-sm font-medium text-gray-700">Email (optional)</label>
                    <input id="facility_email" name="facility_email" type="email"
                           value="{{ old('facility_email', $settings['facility_email']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700">Currency Code</label>
                    <input id="currency" name="currency" type="text" maxlength="3" required
                           value="{{ old('currency', $settings['currency']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm uppercase">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Timezone</label>
                    <p class="mt-1 py-2 text-sm text-gray-500">{{ $timezone }} (fixed, not editable)</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="default_booking_duration_minutes" class="block text-sm font-medium text-gray-700">Default Slot Duration (min)</label>
                    <input id="default_booking_duration_minutes" name="default_booking_duration_minutes" type="number" min="15" step="1" required
                           value="{{ old('default_booking_duration_minutes', $settings['default_booking_duration_minutes']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="max_booking_duration_minutes" class="block text-sm font-medium text-gray-700">Max Booking Duration (min)</label>
                    <input id="max_booking_duration_minutes" name="max_booking_duration_minutes" type="number" min="15" step="1" required
                           value="{{ old('max_booking_duration_minutes', $settings['max_booking_duration_minutes']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="min_booking_notice_minutes" class="block text-sm font-medium text-gray-700">Min Booking Notice (min)</label>
                    <input id="min_booking_notice_minutes" name="min_booking_notice_minutes" type="number" min="0" step="1" required
                           value="{{ old('min_booking_notice_minutes', $settings['min_booking_notice_minutes']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="max_advance_booking_days" class="block text-sm font-medium text-gray-700">Max Advance Booking (days)</label>
                    <input id="max_advance_booking_days" name="max_advance_booking_days" type="number" min="1" step="1" required
                           value="{{ old('max_advance_booking_days', $settings['max_advance_booking_days']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="cancellation_deadline_hours" class="block text-sm font-medium text-gray-700">Cancellation Deadline (hrs)</label>
                    <input id="cancellation_deadline_hours" name="cancellation_deadline_hours" type="number" min="0" step="1" required
                           value="{{ old('cancellation_deadline_hours', $settings['cancellation_deadline_hours']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>

                <div>
                    <label for="max_simultaneous_bookings_per_customer" class="block text-sm font-medium text-gray-700">Max Active Bookings / Customer</label>
                    <input id="max_simultaneous_bookings_per_customer" name="max_simultaneous_bookings_per_customer" type="number" min="1" step="1" required
                           value="{{ old('max_simultaneous_bookings_per_customer', $settings['max_simultaneous_bookings_per_customer']) }}"
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
                </div>
            </div>

            <div>
                <label for="default_court_hourly_rate" class="block text-sm font-medium text-gray-700">Default Court Hourly Rate</label>
                <input id="default_court_hourly_rate" name="default_court_hourly_rate" type="number" min="0" step="0.01" required
                       value="{{ old('default_court_hourly_rate', $settings['default_court_hourly_rate']) }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <button type="submit" class="bg-gray-900 text-white rounded py-2 px-4 text-sm font-medium">
                Save Settings
            </button>
        </form>
    </section>

    <section>
        <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Business Hours</h2>

        <form method="POST" action="{{ route('manage.settings.business-hours.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left font-medium text-gray-500 pb-2 pr-4">Day</th>
                            <th class="text-left font-medium text-gray-500 pb-2 pr-4">Closed</th>
                            <th class="text-left font-medium text-gray-500 pb-2 pr-4">Opens</th>
                            <th class="text-left font-medium text-gray-500 pb-2">Closes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            $byDay = $businessHours->keyBy('day_of_week');
                        @endphp
                        @foreach ($dayNames as $day => $label)
                            @php $hour = $byDay->get($day); @endphp
                            <tr class="border-t">
                                <td class="py-2 pr-4 text-gray-900">{{ $label }}</td>
                                <td class="py-2 pr-4">
                                    <input type="hidden" name="hours[{{ $day }}][is_closed]" value="0">
                                    <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1"
                                           @checked(old("hours.$day.is_closed", $hour?->is_closed))>
                                </td>
                                <td class="py-2 pr-4">
                                    <input type="time" step="1" name="hours[{{ $day }}][opens_at]"
                                           value="{{ old("hours.$day.opens_at", $hour?->opens_at) }}"
                                           class="rounded border-gray-300 shadow-sm text-sm">
                                </td>
                                <td class="py-2">
                                    <input type="time" step="1" name="hours[{{ $day }}][closes_at]"
                                           value="{{ old("hours.$day.closes_at", $hour?->closes_at) }}"
                                           class="rounded border-gray-300 shadow-sm text-sm">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="submit" class="bg-gray-900 text-white rounded py-2 px-4 text-sm font-medium">
                Save Business Hours
            </button>
        </form>
    </section>
@endsection
