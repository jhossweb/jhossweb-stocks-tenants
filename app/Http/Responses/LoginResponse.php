<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Tras un login exitoso, un super admin central va al panel de
     * administración; el resto (usuarios de tenant y no admins) va al home
     * de Fortify.
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
