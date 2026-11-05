<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Enum\Tenancy\TenantPermission;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TenantPermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/Permissions', [
            'permission_groups' => TenantPermission::grouped(),
        ]);
    }
}
