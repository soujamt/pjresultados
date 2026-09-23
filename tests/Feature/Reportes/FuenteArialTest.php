<?php

use App\Services\Reportes\FuenteArial;

function carpetaDeFuentes(string ...$archivos): string
{
    $carpeta = sys_get_temp_dir().'/fuentes-'.uniqid();
    mkdir($carpeta);

    foreach ($archivos as $archivo) {
        touch("{$carpeta}/{$archivo}");
    }

    return $carpeta;
}

it('ubica Arial con los nombres de archivo de Linux en la carpeta configurada', function () {
    $carpeta = carpetaDeFuentes('Arial.ttf', 'Arial_Bold.ttf');
    config(['app.fuente_arial' => $carpeta]);

    // En Windows los nombres no distinguen mayúsculas: «arial.ttf» también encuentra «Arial.ttf».
    expect(array_map('strtolower', (array) app(FuenteArial::class)->ubicar()))->toBe([
        'normal' => strtolower("{$carpeta}/Arial.ttf"),
        'negrita' => strtolower("{$carpeta}/Arial_Bold.ttf"),
    ]);
});

it('no usa Arial si falta la negrita', function () {
    config(['app.fuente_arial' => carpetaDeFuentes('arial.ttf')]);

    expect(app(FuenteArial::class)->ubicar())->toBeNull();
});
