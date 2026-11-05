<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EnsureUserIsActive
{
    public function handle(Request $request, \Closure $next): mixed
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            auth()->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            throw ValidationException::withMessages([
                'email' => 'Tu usuario está inactivo. Contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}
