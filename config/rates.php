<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Aviso de tasa de cambio
    |---------------------------------------------------------------------------
    |
    | Controla el recordatorio global que aparece en el layout del tenant cuando
    | la tasa de cambio está ausente o vencida. Sin tasa vigente, las
    | conversiones y el diferencial cambiario de ventas, compras, gastos y
    | cobranza se calculan con valores viejos.
    |
    */

    // Desactiva el recordatorio sin tocar código.
    'notice_enabled' => (bool) env('RATES_NOTICE_ENABLED', true),

    /*
    | Días de tolerancia antes de considerar vencida la tasa. 0 = estricto: hace
    | falta una tasa cargada para el día de hoy. Subirlo a 3 o 7 relaja el
    | aviso para negocios que no actualizan la tasa cada jornada.
    */
    'stale_after_days' => (int) env('RATES_STALE_AFTER_DAYS', 0),

    /*
    | A partir de cuántos días vencida la tasa pasa de warning a critical. No
    | depende del umbral de tolerancia: es solo la escalada visual del aviso.
    */
    'critical_after_days' => (int) env('RATES_CRITICAL_AFTER_DAYS', 30),

];
