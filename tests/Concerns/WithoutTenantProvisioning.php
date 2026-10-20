<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events;

/**
 * Desactiva el pipeline de provisioning (crear esquema, migrar, aprovisionar)
 * de stancl/tenancy. Los tests del panel central solo necesitan el registro
 * central del tenant, no su base de datos aislada.
 */
trait WithoutTenantProvisioning
{
    protected function withoutTenantProvisioning(): void
    {
        Event::forget(Events\TenantCreated::class);
        Event::forget(Events\TenantDeleted::class);
        Event::forget(Events\CreatingTenant::class);
        Event::forget(Events\SavingTenant::class);
    }
}
