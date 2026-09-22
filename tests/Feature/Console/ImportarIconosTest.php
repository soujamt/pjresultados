<?php

use App\Console\Commands\ImportarIconos;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->destino = sys_get_temp_dir().'/pj-iconos-'.uniqid();
});

afterEach(function () {
    foreach (glob($this->destino.'/*') ?: [] as $archivo) {
        unlink($archivo);
    }

    @rmdir($this->destino);
});

/**
 * Modulo con la forma que publica @hugeicons/core-free-icons.
 */
function moduloDeHugeicons(string $exportacion): string
{
    return <<<JS
    const {$exportacion} = [
      ["circle", { cx: "12", cy: "12", r: "10", stroke: "currentColor", strokeWidth: "1.5", key: "0" }],
      ["path", { d: "M12 16V12", stroke: "currentColor", strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "1.5", key: "1" }]
    ];

    export { {$exportacion} as default };
    JS;
}

it('convierte un icono de Hugeicons en un icono de Flux', function () {
    Http::fake(['unpkg.com/*' => Http::response(moduloDeHugeicons('InformationSquareIcon'))]);

    $this->artisan('pj:iconos', ['nombres' => ['InformationSquareIcon'], '--destino' => $this->destino])
        ->assertSuccessful();

    $blade = file_get_contents($this->destino.'/information-square.blade.php');

    Http::assertSent(fn ($peticion) => str_contains($peticion->url(), '@hugeicons/core-free-icons@'.ImportarIconos::VERSION.'/dist/esm/InformationSquareIcon.js'));

    expect($blade)
        ->toContain('<circle cx="12" cy="12" r="10" stroke="currentColor" />')
        ->toContain('<path d="M12 16V12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />')
        ->toContain('stroke-width="{{ $strokeWidth }}"')
        ->toContain('data-flux-icon')
        ->toContain('MIT')
        ->not->toContain('key=');
});

it('no reemplaza un Heroicon del mismo nombre sin permiso', function () {
    Http::fake();

    $this->artisan('pj:iconos', ['nombres' => ['user-group'], '--destino' => $this->destino])
        ->assertFailed();

    Http::assertNothingSent();
    expect(file_exists($this->destino.'/user-group.blade.php'))->toBeFalse();
});

it('avisa cuando el icono no existe en Hugeicons', function () {
    Http::fake(['unpkg.com/*' => Http::response('Not found', 404)]);

    $this->artisan('pj:iconos', ['nombres' => ['no-existe-99'], '--destino' => $this->destino])
        ->assertFailed();

    expect(file_exists($this->destino.'/no-existe-99.blade.php'))->toBeFalse();
});

it('reemplaza un icono interno de Flux con un alias y lo recuerda al actualizar', function () {
    Http::fake(['unpkg.com/*' => Http::response(moduloDeHugeicons('ViewIcon'))]);

    $this->artisan('pj:iconos', ['nombres' => ['eye=view'], '--forzar' => true, '--destino' => $this->destino])
        ->assertSuccessful();
    $this->artisan('pj:iconos', ['--actualizar' => true, '--forzar' => true, '--destino' => $this->destino])
        ->assertSuccessful();

    expect(file_get_contents($this->destino.'/eye.blade.php'))->toContain('«view»');

    Http::assertSentCount(2);
    Http::assertSent(fn ($peticion) => str_ends_with($peticion->url(), '/ViewIcon.js'));
    Http::assertNotSent(fn ($peticion) => str_ends_with($peticion->url(), '/EyeIcon.js'));
});

it('vuelve a descargar los iconos ya generados', function () {
    Http::fake(['unpkg.com/*' => Http::response(moduloDeHugeicons('Home01Icon'))]);

    $this->artisan('pj:iconos', ['nombres' => ['home-01'], '--destino' => $this->destino])->assertSuccessful();
    $this->artisan('pj:iconos', ['--actualizar' => true, '--destino' => $this->destino])->assertSuccessful();

    Http::assertSentCount(2);
});
