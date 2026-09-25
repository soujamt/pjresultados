<?php

use App\Enums\CondicionResultado;
use App\Enums\Permiso;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Rol;
use App\Models\Unidad;
use App\Models\Usuario;
use App\Services\Evaluacion\FilaDeResultado;
use App\Services\Evaluacion\ResultadoDePuesto;
use App\Services\Evaluacion\ResultadoService;
use App\Services\Reportes\FuenteArial;
use App\Services\Reportes\PdfDeResultados;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

beforeEach(function () {
    $this->proceso = Proceso::factory()->conPublicacion()->create(['codigo_pro' => '014-2026-UE-UCAYALI']);
    $this->puesto = Puesto::factory()->create([
        'id_pro' => $this->proceso->id_pro,
        'id_uni' => Unidad::factory()->create(['nombre_uni' => 'MÓDULO PENAL CENTRAL']),
        'codigo_pue' => '00309',
        'nombre_pue' => 'SECRETARIO JUDICIAL',
    ]);

    foreach (['GALVEZ DORADO LUCIA' => 26, 'ALARCON SIERRA MATEO' => 19] as $nombres => $aciertos) {
        Examen::factory()->create([
            'id_ins' => Inscripcion::factory()->create(['id_pue' => $this->puesto->id_pue, 'apellidos_nombres_ins' => $nombres]),
            'puntaje_exa' => $aciertos,
            'aciertos_exa' => $aciertos,
            'errores_exa' => 30 - $aciertos,
        ]);
    }
});

function descargarResultados(string $formato, array $filtros = [], ?Usuario $usuario = null)
{
    return test()->actingAs($usuario ?? Usuario::factory()->superAdministrador()->create())
        ->get(route('evaluacion.resultados.descargar', ['proceso' => test()->proceso->codigo_pro, 'formato' => $formato] + $filtros));
}

it('descarga el PDF del Anexo 07 de un puesto', function () {
    $respuesta = descargarResultados('pdf', ['puesto' => $this->puesto->id_pue]);

    $respuesta->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('resultados-evaluacion-tecnica-014-2026-ue-ucayali-00309.pdf');

    expect($respuesta->getContent())->toStartWith('%PDF');
});

it('arma el PDF con el orden de merito, la condicion y el pie de la publicacion', function () {
    $this->view('reportes.resultados-tecnica', [
        'proceso' => $this->proceso,
        'resultados' => app(ResultadoService::class)->porPuesto($this->proceso),
    ])
        ->assertSeeInOrder(['ANEXO N.° 07', 'SECRETARIO JUDICIAL', '00309', 'MÓDULO PENAL CENTRAL'])
        ->assertSeeInOrder(['GALVEZ DORADO LUCIA', '26', '17.33', '5.20', 'APTO'])
        ->assertSeeInOrder(['ALARCON SIERRA MATEO', '19', '12.67', '3.80', 'NO APTO', ResultadoService::NO_ALCANZO])
        ->assertSeeInOrder(['mayor o igual a 3,9 puntos', '28/09/2026', 'convocatorias@ejemplo.gob.pe'])
        ->assertSee('Pucallpa, 27 de Setiembre del año 2026')
        ->assertSee('El Comité de Selección de Personal Permanente');
});

it('pone la cabecera y el cierre una sola vez aunque haya varios puestos', function () {
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00421']);
    Inscripcion::factory()->create(['id_pue' => $otro->id_pue]);

    $html = view('reportes.resultados-tecnica', [
        'proceso' => $this->proceso,
        'resultados' => app(ResultadoService::class)->porPuesto($this->proceso),
    ])->render();

    expect(substr_count($html, 'ANEXO N.° 07'))->toBe(1)
        ->and(substr_count($html, 'RESULTADOS DE LA EVALUACIÓN TÉCNICA'))->toBe(1)
        ->and(substr_count($html, 'Los postulantes con puntaje'))->toBe(1)
        ->and(substr_count($html, 'Código del puesto:'))->toBe(2);
});

it('no muestra numeros de pagina en el PDF', function () {
    /* Con Helvetica, el antiguo pie se detecta en el flujo del PDF. */
    config(['app.fuente_arial' => sys_get_temp_dir().'/sin-arial']);

    $pdf = app(PdfDeResultados::class)->generar($this->proceso, app(ResultadoService::class)->porPuesto($this->proceso));

    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf->output(), $flujos);
    $texto = implode("\n", array_map(fn (string $flujo): string => (string) @gzuncompress($flujo), $flujos[1]));

    expect(str_contains($texto, mb_convert_encoding('SECRETARIO JUDICIAL', 'UTF-16BE', 'UTF-8')))->toBeTrue();
    expect(str_contains($texto, 'gina 1 de 1'))->toBeFalse();
});

it('descarga el Excel en una sola hoja, como el PDF', function () {
    $otro = Puesto::factory()->create(['id_pro' => $this->proceso->id_pro, 'codigo_pue' => '00421']);
    Inscripcion::factory()->create(['id_pue' => $otro->id_pue]);

    $respuesta = descargarResultados('excel')
        ->assertOk()
        ->assertDownload('resultados-evaluacion-tecnica-014-2026-ue-ucayali.xlsx');

    $libro = IOFactory::load($respuesta->getFile()->getPathname());
    $hoja = $libro->getActiveSheet();
    $texto = fn (string $celda): string => (string) ($hoja->getCell($celda)->getValue() instanceof RichText
        ? $hoja->getCell($celda)->getValue()->getPlainText()
        : $hoja->getCell($celda)->getValue());

    expect($libro->getSheetNames())->toBe(['Anexo 07'])
        ->and($texto('B1'))->toBe('ANEXO N.° 07')
        ->and($hoja->rangeToArray('B16:H17', formatData: false))->toBe([
            [1, 'GALVEZ DORADO LUCIA', 26, 17.33, 5.2, 'APTO', null],
            [2, 'ALARCON SIERRA MATEO', 19, 12.67, 3.8, 'NO APTO', ResultadoService::NO_ALCANZO],
        ])
        ->and($texto('B20'))->toBe('Código del puesto: 00421')
        ->and($texto('B26'))->toStartWith('Los postulantes con puntaje de evaluación técnica mayor o igual a 3,9 puntos')
        ->and($texto('B28'))->toBe("Pucallpa, 27 de Setiembre del año 2026\nEl Comité de Selección de Personal Permanente")
        ->and($hoja->getHeaderFooter()->getOddFooter())->toContain('Página &P de &N');
});

it('no descarga sin los datos de la publicacion', function () {
    $this->proceso->update(['correo_documentos_pro' => null]);

    descargarResultados('pdf')->assertUnprocessable();
});

it('no descarga sin el permiso de exportar', function () {
    $usuario = Usuario::factory()->create(['id_rol' => Rol::factory()->con([Permiso::ResultadosVer])]);

    descargarResultados('pdf', usuario: $usuario)->assertForbidden();
});

it('no descarga el puesto de otro proceso', function () {
    $ajeno = Puesto::factory()->create();

    descargarResultados('pdf', ['puesto' => $ajeno->id_pue])->assertNotFound();
});

/**
 * Arma el PDF con puestos ficticios de los tamaños indicados y devuelve en qué
 * página quedó cada cosa: por puesto, sus datos, las páginas donde se dibujó
 * la fila de títulos y cada una de sus filas; y el párrafo del pie y la firma.
 *
 * @param  list<int>  $tamanos
 * @return array{puestos: list<array{datos: int, titulos: list<int>, filas: list<int>}>, pie: int, firma: int}
 */
function paginasDelPdf(Proceso $proceso, array $tamanos, bool $repetirTitulos = true): array
{
    $resultados = array_map(function (int $indice, int $cantidad): ResultadoDePuesto {
        $puesto = new Puesto(['codigo_pue' => sprintf('%05d', 300 + $indice), 'nombre_pue' => "PUESTO DE PRUEBA {$indice}"]);
        $puesto->setRelation('unidad', new Unidad(['nombre_uni' => "UNIDAD DE PRUEBA {$indice}"]));

        return new ResultadoDePuesto($puesto, array_map(fn (int $numero): FilaDeResultado => new FilaDeResultado(
            $numero,
            new Inscripcion(['documento_ins' => sprintf('7%07d', $numero), 'apellidos_nombres_ins' => "POSTULANTE {$numero} DE PRUEBA"]),
            10, 10 * 20 / 30, 2.0, CondicionResultado::NoApto, ResultadoService::NO_ALCANZO, true, false,
        ), range(1, $cantidad)));
    }, array_keys($tamanos), $tamanos);

    $paginas = ['puestos' => [], 'pie' => 0, 'firma' => 0];
    $pdf = Pdf::loadView('reportes.resultados-tecnica', [
        'proceso' => $proceso,
        'resultados' => $resultados,
        'repetirTitulos' => $repetirTitulos,
    ])->setPaper('a4', 'landscape');
    app(FuenteArial::class)->registrar($pdf->getDomPDF());

    $pdf->getDomPDF()->setCallbacks([[
        'event' => 'begin_frame',
        'f' => function ($frame, $canvas) use (&$paginas): void {
            $nodo = $frame->get_node();

            if (! $nodo instanceof DOMElement) {
                return;
            }

            $pagina = $canvas->get_page_number();
            $clase = $nodo->getAttribute('class');
            $tabla = $nodo->parentNode?->parentNode;

            if ($clase === 'datos-del-puesto') {
                $paginas['puestos'][] = ['datos' => $pagina, 'titulos' => [], 'filas' => []];
            } elseif ($nodo->nodeName === 'tr' && $tabla instanceof DOMElement && $tabla->getAttribute('class') === 'resultados') {
                $paginas['puestos'][array_key_last($paginas['puestos'])][$clase === 'titulos' ? 'titulos' : 'filas'][] = $pagina;
            } elseif (in_array($clase, ['pie', 'firma'], true)) {
                $paginas[$clase] = $pagina;
            }
        },
    ]]);
    $pdf->output();

    return $paginas;
}

it('no deja nada suelto al cortar las paginas del PDF', function (array $tamanos, bool $repetirTitulos = true) {
    $paginas = paginasDelPdf($this->proceso, $tamanos, $repetirTitulos);

    foreach ($paginas['puestos'] as $puesto) {
        $filas = $puesto['filas'];
        $porPagina = array_count_values($filas);

        expect([$puesto['datos'], $puesto['titulos'][0]])->toBe([$filas[0], $filas[0]], 'Los datos del puesto o sus títulos quedaron separados de la tabla.')
            ->and($porPagina[$filas[0]])->toBeGreaterThanOrEqual(min(3, count($filas)), 'La tabla empieza con muy pocas filas.')
            ->and($porPagina[end($filas)])->toBeGreaterThanOrEqual(min(4, count($filas)), 'La tabla termina con muy pocas filas en otra página.');
    }

    $ultimaFila = end(end($paginas['puestos'])['filas']);

    expect([$paginas['pie'], $paginas['firma']])->toBe([$ultimaFila, $ultimaFila], 'El cierre quedó en otra página.');
})->with([
    /*
     * Documentos en los que, sin las reglas de salto de página de la vista,
     * algo queda suelto. Cambian con la fuente: el PDF sale en Arial donde el
     * servidor la tiene y en Helvetica donde no (como en la integración continua).
     */
    'Arial: la firma sola' => [[12]],
    'Arial: el pie sin filas' => [[15]],
    'Arial: una sola fila en la última página' => [[18]],
    'Arial: el cierre en otra página' => [[3, 12, 3, 7]],
    'Arial: una tabla termina con una fila' => [[4, 12, 3, 7]],
    'Arial: el puesto separado de su tabla' => [[8, 12, 3, 7]],
    'Helvetica: la firma sola' => [[17]],
    'Helvetica: el pie sin filas' => [[20]],
    'Helvetica: una sola fila en la última página' => [[23]],
    'Helvetica: el cierre en otra página' => [[8, 12, 3, 7]],
    'Helvetica: una tabla empieza con una fila' => [[12, 12, 3, 7]],
    'Helvetica: el puesto separado de su tabla' => [[13, 12, 3, 7]],
    // Con los títulos solo al inicio la fila de títulos es parte del cuerpo de la tabla: las mismas reglas valen.
    'títulos solo al inicio: varios puestos' => [[8, 12, 3, 7], false],
    'títulos solo al inicio: la firma con las últimas filas' => [[45], false],
    'títulos solo al inicio: el último puesto largo' => [[12, 3, 30], false],
]);

it('repite la fila de titulos en cada pagina solo si se pide', function (bool $repetirTitulos) {
    $tabla = paginasDelPdf($this->proceso, [45], $repetirTitulos)['puestos'][0];
    $paginasDeLaTabla = array_values(array_unique($tabla['filas']));

    expect(count($paginasDeLaTabla))->toBeGreaterThan(1)
        ->and($tabla['titulos'])->toBe($repetirTitulos ? $paginasDeLaTabla : [$paginasDeLaTabla[0]]);
})->with([
    'títulos en cada página' => true,
    'títulos solo al inicio' => false,
]);

it('descarga el PDF con los titulos solo al inicio de cada tabla', function () {
    $usuario = Usuario::factory()->superAdministrador()->create();

    descargarResultados('pdf', ['puesto' => $this->puesto->id_pue, 'repetir_titulos' => 0], $usuario)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    descargarResultados('pdf', ['repetir_titulos' => 'talvez'], $usuario)->assertInvalid('repetir_titulos');
});

it('descarga el PDF en Arial cuando el servidor la tiene instalada', function () {
    $respuesta = descargarResultados('pdf', ['puesto' => $this->puesto->id_pue])->assertOk();

    expect($respuesta->getContent())->toContain('+ArialMT')->toContain('+Arial-BoldMT');
})->skip(fn () => app(FuenteArial::class)->ubicar() === null, 'El equipo no tiene Arial instalada; el PDF sale en Helvetica.');
