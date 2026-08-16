@extends('layouts.app', ['title' => 'Settings'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-8">Settings</h1>

    <section class="mb-10">
        <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Facility &amp; Booking Rules</h2>

        <x-card class="max-w-5xl">
        <form method="POST" action="{{ route('manage.settings.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="facility_logo" class="block text-sm font-medium text-slate-700">Facility Logo (optional)</label>
                <p class="text-xs text-slate-500 mb-1">Shown in the header/nav instead of the facility name text, if uploaded.</p>
                @if ($settings['facility_logo'])
                    <div class="flex items-center gap-3 mt-1 mb-2">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['facility_logo']) }}" alt="Facility logo" class="w-16 h-16 object-contain rounded-lg border border-slate-200 bg-white p-1">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_facility_logo" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            Remove logo
                        </label>
                    </div>
                @endif
                <input id="facility_logo" name="facility_logo" type="file" accept="image/*"
                       class="mt-1 block w-full text-sm text-slate-500 cursor-pointer file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-accent file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-slate-900 file:shadow-sm file:transition-colors hover:file:bg-[#7A9F20]">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-4">
                <div>
                    <label for="facility_name" class="block text-sm font-medium text-slate-700">Facility Name</label>
                    <input id="facility_name" name="facility_name" type="text" required
                           value="{{ old('facility_name', $settings['facility_name']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="facility_address" class="block text-sm font-medium text-slate-700">Address (optional)</label>
                    <input id="facility_address" name="facility_address" type="text"
                           value="{{ old('facility_address', $settings['facility_address']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="facility_phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input id="facility_phone" name="facility_phone" type="text"
                           value="{{ old('facility_phone', $settings['facility_phone']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="facility_email" class="block text-sm font-medium text-slate-700">Email (optional)</label>
                    <input id="facility_email" name="facility_email" type="email"
                           value="{{ old('facility_email', $settings['facility_email']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-slate-700">Currency Code</label>
                    <input id="currency" name="currency" type="text" maxlength="3" required
                           value="{{ old('currency', $settings['currency']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm uppercase focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Timezone</label>
                    <p class="mt-1 py-2 text-sm text-slate-500">{{ $timezone }} (fixed, not editable)</p>
                </div>

                <div>
                    <label for="default_booking_duration_minutes" class="block text-sm font-medium text-slate-700">Default Slot Duration (min)</label>
                    <input id="default_booking_duration_minutes" name="default_booking_duration_minutes" type="number" min="15" step="1" required
                           value="{{ old('default_booking_duration_minutes', $settings['default_booking_duration_minutes']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="max_booking_duration_minutes" class="block text-sm font-medium text-slate-700">Max Booking Duration (min)</label>
                    <input id="max_booking_duration_minutes" name="max_booking_duration_minutes" type="number" min="15" step="1" required
                           value="{{ old('max_booking_duration_minutes', $settings['max_booking_duration_minutes']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="min_booking_notice_minutes" class="block text-sm font-medium text-slate-700">Min Booking Notice (min)</label>
                    <input id="min_booking_notice_minutes" name="min_booking_notice_minutes" type="number" min="0" step="1" required
                           value="{{ old('min_booking_notice_minutes', $settings['min_booking_notice_minutes']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="max_advance_booking_days" class="block text-sm font-medium text-slate-700">Max Advance Booking (days)</label>
                    <input id="max_advance_booking_days" name="max_advance_booking_days" type="number" min="1" step="1" required
                           value="{{ old('max_advance_booking_days', $settings['max_advance_booking_days']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="cancellation_deadline_hours" class="block text-sm font-medium text-slate-700">Cancellation Deadline (hrs)</label>
                    <input id="cancellation_deadline_hours" name="cancellation_deadline_hours" type="number" min="0" step="1" required
                           value="{{ old('cancellation_deadline_hours', $settings['cancellation_deadline_hours']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="max_simultaneous_bookings_per_customer" class="block text-sm font-medium text-slate-700">Max Active Bookings / Customer</label>
                    <input id="max_simultaneous_bookings_per_customer" name="max_simultaneous_bookings_per_customer" type="number" min="1" step="1" required
                           value="{{ old('max_simultaneous_bookings_per_customer', $settings['max_simultaneous_bookings_per_customer']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="default_court_hourly_rate" class="block text-sm font-medium text-slate-700">Default Court Hourly Rate</label>
                    <input id="default_court_hourly_rate" name="default_court_hourly_rate" type="number" min="0" step="0.01" required
                           value="{{ old('default_court_hourly_rate', $settings['default_court_hourly_rate']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="payment_hold_minutes" class="block text-sm font-medium text-slate-700">Payment Hold Window (min)</label>
                    <input id="payment_hold_minutes" name="payment_hold_minutes" type="number" min="1" max="120" step="1" required
                           value="{{ old('payment_hold_minutes', $settings['payment_hold_minutes']) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-xs text-slate-500 mt-1">Time before an unpaid hold releases.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-1">
                <div>
                    <label for="payment_instructions" class="block text-sm font-medium text-slate-700">Guest Payment Instructions</label>
                    <p class="text-xs text-slate-500 mb-1">Shown after selecting slots - how to pay and where to enter the reference number.</p>
                    <textarea id="payment_instructions" name="payment_instructions" rows="3"
                              class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('payment_instructions', $settings['payment_instructions']) }}</textarea>
                </div>

                <div>
                    <label for="payment_qr_code" class="block text-sm font-medium text-slate-700">Payment QR Code (optional)</label>
                    <p class="text-xs text-slate-500 mb-1">Shown on the payment screen so customers can scan to pay.</p>
                    @if ($settings['payment_qr_code'])
                        <div class="flex items-center gap-3 mt-1 mb-2">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['payment_qr_code']) }}" alt="Payment QR code" class="w-16 h-16 object-contain rounded-lg border border-slate-200 bg-white p-1">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remove_payment_qr_code" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Remove QR code
                            </label>
                        </div>
                    @endif
                    <input id="payment_qr_code" name="payment_qr_code" type="file" accept="image/*"
                           class="mt-1 block w-full text-sm text-slate-500 cursor-pointer file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-accent file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-slate-900 file:shadow-sm file:transition-colors hover:file:bg-[#7A9F20]">
                </div>
            </div>

            <x-button type="submit">Save Settings</x-button>
        </form>
        </x-card>
    </section>

    <section class="mb-10">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-medium text-slate-500 uppercase">Courts</h2>
            <x-button tag="a" href="{{ route('admin.courts.create') }}" variant="secondary" class="!py-1.5 !px-3 text-xs">New Court</x-button>
        </div>

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
                                <td class="py-3 pr-4 text-slate-600">₱{{ number_format($court->hourly_rate, 0) }} day / ₱{{ number_format($court->evening_hourly_rate, 0) }} evening</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="$court->status === \App\Enums\CourtStatus::Active ? 'green' : 'slate'">{{ $court->status->label() }}</x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('admin.courts.edit', $court) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section>
        <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Business Hours</h2>
        <p class="text-sm text-slate-500 mb-3">To stay open past midnight, set a closing time earlier than the opening time (e.g. opens 6:00 AM, closes 2:00 AM) - it's treated as closing the next morning.</p>

        <form method="POST" action="{{ route('manage.settings.business-hours.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Day</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Closed</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Opens</th>
                            <th class="text-left font-medium text-slate-500 py-3 pr-4">Closes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            $byDay = $businessHours->keyBy('day_of_week');
                        @endphp
                        @foreach ($dayNames as $day => $label)
                            @php $hour = $byDay->get($day); @endphp
                            <tr class="border-t border-slate-100">
                                <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $label }}</td>
                                <td class="py-3 pr-4">
                                    <input type="hidden" name="hours[{{ $day }}][is_closed]" value="0">
                                    <input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1"
                                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                           @checked(old("hours.$day.is_closed", $hour?->is_closed))>
                                </td>
                                <td class="py-3 pr-4">
                                    <input type="time" step="1" name="hours[{{ $day }}][opens_at]"
                                           value="{{ old("hours.$day.opens_at", $hour?->opens_at) }}"
                                           class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                                <td class="py-3 pr-4">
                                    <input type="time" step="1" name="hours[{{ $day }}][closes_at]"
                                           value="{{ old("hours.$day.closes_at", $hour?->closes_at) }}"
                                           class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-button type="submit">Save Business Hours</x-button>
        </form>
    </section>
@endsection
