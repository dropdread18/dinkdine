<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessHoursRequest;
use App\Http\Requests\SettingRequest;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Keys shown/edited on the settings page. `timezone` is deliberately
     * excluded - it's a structural assumption baked into every date/time
     * calculation in the app (AvailabilityService, ReportingService, etc.),
     * not a value that can be safely swapped from a form field.
     */
    private const KEYS = [
        'facility_name',
        'facility_address',
        'facility_phone',
        'facility_email',
        'facility_facebook',
        'currency',
        'default_booking_duration_minutes',
        'max_booking_duration_minutes',
        'min_booking_notice_minutes',
        'max_advance_booking_days',
        'cancellation_deadline_hours',
        'max_simultaneous_bookings_per_customer',
        'default_court_hourly_rate',
        'payment_hold_minutes',
        'payment_instructions',
        'open_play_link',
        'convenience_fee',
    ];

    public function index(): View
    {
        $settings = collect(self::KEYS)->mapWithKeys(
            fn (string $key) => [$key => Setting::get($key)]
        );
        $settings['facility_logo'] = Setting::get('facility_logo');

        return view('admin.settings.index', [
            'settings' => $settings,
            'businessHours' => BusinessHour::orderBy('day_of_week')->get(),
            'timezone' => Setting::get('timezone', config('app.timezone')),
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
            'paymentMethods' => PaymentMethod::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        foreach ($request->safe()->only(self::KEYS) as $key => $value) {
            Setting::set($key, (string) $value);
        }

        // Not in self::KEYS - it's a stored-file path, not a plain text
        // value, so it can't go through the blind (string) cast loop above
        // (that would store the file's temp path instead of the public-disk
        // path, and would wipe the existing logo on every save with no new
        // file chosen). Same store/replace pattern as CourtController's
        // image handling: delete the old file before writing the new path.
        if ($request->boolean('remove_facility_logo')) {
            if ($existing = Setting::get('facility_logo')) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('facility_logo', '');
        } elseif ($request->hasFile('facility_logo')) {
            if ($existing = Setting::get('facility_logo')) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('facility_logo', $request->file('facility_logo')->store('settings', 'public'));
        }

        return redirect()->route('manage.settings.index')->with('status', 'Settings updated.');
    }

    public function updateBusinessHours(BusinessHoursRequest $request): RedirectResponse
    {
        foreach ($request->validated('hours') as $day => $row) {
            BusinessHour::updateOrCreate(['day_of_week' => $day], [
                'is_closed' => ! empty($row['is_closed']),
                'opens_at' => empty($row['is_closed']) ? $row['opens_at'] : null,
                'closes_at' => empty($row['is_closed']) ? $row['closes_at'] : null,
            ]);
        }

        return redirect()->route('manage.settings.index')->with('status', 'Business hours updated.');
    }
}
