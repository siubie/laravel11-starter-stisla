<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class MyRecapchaV3 implements ValidationRule
{
    public function __construct(
        private ?string $action = null,
        private ?float $minScore = null
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Send a POST request to the google siteverify service to validate the
        $siteVerify = Http::asForm()
            ->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => "6LftjtgpAAAAAKsoDS8EvT6jfEHS8WLpMaJUeTk-",
                'response' => $value,
            ]);

        // dd($siteVerify->body());
        // This happens if google denied our request with an error
        if ($siteVerify->failed()) {
            $fail('Google reCAPTCHA was not able to verify the form, please try again.');
            return;
        }

        // This means Google successfully processed our POST request. We still need to check the results!
        if ($siteVerify->successful()) {
            $body = $siteVerify->json();

            // When this fails it means the browser didn't send a correct code. This means it's very likely a bot we should block
            if ($body['success'] !== true) {
                $fail('Your form submission failed the Google reCAPTCHA verification, please try again.');
                return;
            }

            // When this fails it means the action didn't match the one set in the button's data-action.
            // Either a bot or a code mistake. Compare form data-action and value passed to $action (should be equal).
            if (!is_null($this->action) && $this->action != $body['action']) {
                $fail('The action found in the form didn\'t match the Google reCAPTCHA action, please try again.');
                return;
            }

            // If we set a minScore treshold, verify that the spam score didn't go below it
            // More info can be found at: https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
            if (!is_null($this->minScore) && $this->minScore > $body['score']) {
                $fail('The Google reCAPTCHA verification score was too low, please try again.');
                return;
            }
        }        //
    }
}
