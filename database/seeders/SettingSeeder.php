<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Requirements.md §43-44: configuration values, not hard-coded rules.
     */
    public function run(): void
    {
        $defaults = [
            'facility_name' => 'Pickleball Court Booking',
            'facility_address' => '',
            'facility_phone' => '',
            'facility_email' => '',
            'facility_facebook' => '',
            'currency' => 'PHP',
            'timezone' => 'Asia/Manila',
            'default_booking_duration_minutes' => '60',
            'max_advance_booking_days' => '30',
            'min_booking_notice_minutes' => '30',
            'max_booking_duration_minutes' => '120',
            'cancellation_deadline_hours' => '4',
            'max_simultaneous_bookings_per_customer' => '3',
            'default_court_hourly_rate' => '300',
            'payment_hold_minutes' => '10',
            'payment_instructions' => "GCash: 0917-000-0000 (Facility Name)\nPlease enter your reference number after paying.",
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
