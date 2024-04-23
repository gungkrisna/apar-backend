<?php

namespace App\Rules;

use App\Models\Invitation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueInvitationEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if there is any active invitation with the same email
        $exists = Invitation::where('email', $value)
            ->where('expired_at', '>', now()) // Only non-expired invitations
            ->exists();

        if ($exists) {
            $fail('Alamat email ' . $value . ' sudah diundang.');
        };
    }
}
