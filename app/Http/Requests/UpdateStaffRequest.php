<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->route('staff'))],
            'phone' => ['nullable', 'string', 'max:30'],
            // Nullable/optional - blank leaves the existing password
            // untouched. This is the only way an admin can reset a staff
            // member's password (they have no self-service "forgot
            // password" trigger an admin can act on).
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
