<?php

use App\Services\FactChecker;

it('acepta cuerpos cuyas cifras grandes están en el payload', function () {
    $payload = ['importe_empresa' => 720000.0, 'share_pct' => 72.0, 'year' => 2024];
    $body = 'La empresa concentró 720.000 € en 2024, el 72% del gasto, con 3 contratos.';

    expect((new FactChecker)->verificar($body, $payload))->toBeTrue();
});

it('rechaza una cifra grande inventada que no está en el payload', function () {
    $payload = ['importe_empresa' => 720000.0, 'share_pct' => 72.0];
    $body = 'La empresa facturó 1.500.000 € adicionales según fuentes.';

    $fc = new FactChecker;
    expect($fc->verificar($body, $payload))->toBeFalse()
        ->and($fc->primeraCifraInvalida($body, $payload))->toBe(1500000.0);
});

it('permite cifras pequeñas (años, porcentajes, recuentos) sin exigir match', function () {
    $payload = ['importe' => 5_000_000.0];
    $body = 'En 2023 hubo 12 adjudicaciones que suponen el 40% del total.';

    expect((new FactChecker)->verificar($body, $payload))->toBeTrue();
});

it('admite tolerancia de redondeo en importes', function () {
    $payload = ['importe' => 1_234_567.0];
    $body = 'El contrato ascendió a 1.234.567 €.';

    expect((new FactChecker)->verificar($body, $payload))->toBeTrue();
});
