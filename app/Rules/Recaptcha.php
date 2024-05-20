<?php

namespace App\Rules;

use Closure;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ];
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', $data);

        // dd($response->json(), $response->json('score'));
        //return fail if the response is failed
        if ($response->failed()) {
            $fail('Request to recaptcha server failed, please contact DosenNgoding');
        }

        if ($response->json('error-codes')) {
            $errorMessage = implode(', ', $response->json('error-codes'));
            $fail("Recaptcha validation failed : $errorMessage");
        }

        //return fail if the score is less than the minimum score
        // dd($response->json('score'));
        if ($response->json('score') && ($response->json('score') < 0.5)) {
            $fail('Recaptcha validation failed, score too low');
        }
    }
}
