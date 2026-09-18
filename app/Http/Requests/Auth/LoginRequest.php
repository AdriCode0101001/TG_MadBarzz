<?php

namespace App\Http\Requests\Auth;

use App\Services\LoginLockoutService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     * Accepts email address or username (name field).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login    = $this->string('email')->toString();
        $password = $this->string('password')->toString();

        // Detectar si es email o nombre de usuario
        $isEmail  = filter_var($login, FILTER_VALIDATE_EMAIL);
        $field    = $isEmail ? 'email' : 'name';

        $credentials = [
            $field     => $login,
            'password' => $password,
        ];

        $lockout = app(LoginLockoutService::class);

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $lockout->registrarFallo($login, $this->ip());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $lockout->registrarExito($login, $this->ip());
    }

    /**
     * Ensure the login request is not rate limited (bloqueo progresivo:
     * ver LoginLockoutService — 5 fallos -> 3 min, y por cada 3 fallos
     * adicionales el bloqueo se duplica hasta un tope de 15 min).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $login   = $this->string('email')->toString();
        $lockout = app(LoginLockoutService::class);

        if (! $lockout->estaBloqueado($login, $this->ip())) {
            return;
        }

        event(new Lockout($this));

        $seconds = $lockout->segundosRestantes($login, $this->ip());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Llave de rate limiting de esta request (login+IP). Delegada al
     * servicio para que toda la app calcule siempre la misma llave.
     */
    public function throttleKey(): string
    {
        return app(LoginLockoutService::class)->throttleKey(
            $this->string('email')->toString(),
            $this->ip()
        );
    }
}
