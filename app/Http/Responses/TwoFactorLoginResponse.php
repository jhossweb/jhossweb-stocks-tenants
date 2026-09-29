<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Misma lógica que LoginResponse pero aplicada al flujo de 2FA.
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        $destination = $request->user()?->is_admin
            ? route('admin.tenants.index')
            : config('fortify.home');

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended($destination);
    }
}
