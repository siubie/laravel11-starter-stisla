<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
        //register
        Fortify::registerView(function () {
            return view('auth.register');
        });

        //login
        Fortify::loginView(function () {
            return view('auth.login');
        });

        //forgot
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        //reset
        Fortify::resetPasswordView(function ($request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        //verify account
        Fortify::verifyEmailView(function () {
            return view('auth.verify');
        });

        //custom authentication
        Fortify::authenticateUsing(function (Request $request) {
            //validate request
            // $recaptcha = $request->get('g-recaptcha-response');
            $score = RecaptchaV3::verify($request->get('g-recaptcha-response'), 'login');
            // dd($score);
            if ($score > 0.7) {
                // go
                $credentials = $request->validate([
                    'email' => 'required|email',
                    'password' => 'required',
                ]);
                //unset recaptcha
                //check user
                if (Auth::attempt($credentials)) {
                    return Auth::user();
                }
            } elseif ($score > 0.3) {
                // require additional email verification
            } else {
                dd($score);
                // return abort(400, 'You are most likely a bot');
            }
            //return false
            return false;
        });
    }
}
