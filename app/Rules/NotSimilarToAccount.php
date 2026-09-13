<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Blocks a new password that's identical to the account's current password,
 * or that trivially incorporates the account holder's own name, email, or
 * mobile number — resetting a password to something derived from the
 * account's own identity defeats the point (NIST 800-63B recommends
 * screening for exactly this).
 */
class NotSimilarToAccount implements ValidationRule
{
    public function __construct(private readonly User $account) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->account->password && Hash::check($value, $this->account->password)) {
            $fail('The new password must be different from the current password.');

            return;
        }

        $needle = Str::lower($value);

        foreach ($this->identifyingStrings() as $identifier) {
            if ($identifier !== '' && Str::contains($needle, Str::lower($identifier))) {
                $fail("The password is too similar to the account's name, email, or phone number.");

                return;
            }
        }
    }

    /**
     * Substrings a password shouldn't be allowed to contain — words from the
     * account's name (short ones like "de"/"jr" are skipped, they'd false-positive
     * on almost anything), the email's local part, and the mobile number.
     *
     * @return array<int, string>
     */
    private function identifyingStrings(): array
    {
        $strings = [];

        foreach (preg_split('/\s+/', (string) $this->account->name) as $word) {
            if (mb_strlen($word) >= 4) {
                $strings[] = $word;
            }
        }

        if ($this->account->email) {
            $strings[] = Str::before($this->account->email, '@');
        }

        if ($this->account->mobile_number) {
            $strings[] = $this->account->mobile_number;
        }

        return $strings;
    }
}
