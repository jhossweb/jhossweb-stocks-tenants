<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('falla cuando el tenant destino no existe', function (): void {
    artisan('legacy:migrate', ['tenant' => 'no-existe'])
        ->expectsOutputToContain('no encontrado')
        ->assertExitCode(1);
});
