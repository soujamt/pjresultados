<?php

use App\Services\Excel\EscritorXlsx;
use App\Services\Excel\LectorXlsx;

it('escribe un excel que se vuelve a leer igual', function () {
    $ruta = (string) tempnam(sys_get_temp_dir(), 'xlsx');

    (new EscritorXlsx('Postulantes'))
        ->cabecera(['DNI', 'APELLIDOS Y NOMBRES'], [14, 55])
        ->fila(['01234567', 'MUÑOZ & PÉREZ <JR>'])
        ->fila(['71234567', 'SERRANO "LA" CASTILLO'])
        ->escribir($ruta);

    $lector = new LectorXlsx($ruta);

    expect($lector->hojas())->toBe(['Postulantes'])
        ->and(array_values(iterator_to_array($lector->filas('Postulantes'))))->toBe([
            ['DNI' => '01234567', 'APELLIDOS Y NOMBRES' => 'MUÑOZ & PÉREZ <JR>'],
            ['DNI' => '71234567', 'APELLIDOS Y NOMBRES' => 'SERRANO "LA" CASTILLO'],
        ]);
});

it('guarda las celdas como texto para no perder los ceros del DNI', function () {
    $ruta = (string) tempnam(sys_get_temp_dir(), 'xlsx');

    (new EscritorXlsx)->cabecera(['DNI'])->fila(['01234567'])->escribir($ruta);

    $zip = new ZipArchive;
    $zip->open($ruta);
    $hoja = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $estilos = (string) $zip->getFromName('xl/styles.xml');
    $zip->close();

    expect($hoja)->toContain('t="s" s="1"')
        ->and($estilos)->toContain('<xf numFmtId="49"');
});

it('rechaza un nombre de hoja que Excel no admite', function () {
    new EscritorXlsx('Postulantes/2026');
})->throws(RuntimeException::class);
