<?php

namespace App\Services\Evaluacion;

use App\Enums\TipoImportacion;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Usuario;
use App\Services\Seleccion\AnexoPostulantes;
use App\Services\Seleccion\BitacoraDeImportaciones;
use App\Services\Seleccion\ResultadoImportacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Carga las hojas calificadas por la lectora optica y las cruza por DNI con
 * los inscritos del proceso.
 *
 * El puntaje es el numero de aciertos: la «Nota» del archivo tiene que ser
 * igual a los aciertos, y aciertos + errores + blancos + dobles tiene que dar
 * el mismo total de preguntas en todas las hojas. Cualquier diferencia indica
 * una hoja mal leida o una lectora mal configurada.
 *
 * `analizar()` revisa el archivo sin escribir nada: es la vista previa.
 * `importar()` vuelve a analizarlo (los datos pudieron cambiar entretanto) y,
 * si no hay ninguna observacion, lo guarda. Volver a subir un archivo
 * actualiza por DNI las hojas que cambiaron, asi que se pueden cargar los
 * lotes de la lectora uno tras otro.
 *
 * Que el nombre impreso en la hoja no coincida con el del padron no detiene
 * la carga (el DNI es la llave), pero se marca para revisarlo en pantalla.
 *
 * @phpstan-import-type Hoja from AnalisisDeExamenes
 */
class ImportadorExamenes
{
    private const TAMANO_LOTE = 500;

    private const CONTEOS = [
        ArchivoDeLectora::ACIERTOS => 'Aciertos',
        ArchivoDeLectora::ERRORES => 'Errores',
        ArchivoDeLectora::BLANCOS => 'Blancos',
        ArchivoDeLectora::DOBLES => 'Dobles',
    ];

    public function __construct(private readonly BitacoraDeImportaciones $bitacora) {}

    /**
     * @throws RuntimeException cuando el archivo no tiene el formato de la
     *                          lectora o el proceso no tiene inscritos.
     */
    public function analizar(Proceso $proceso, string $archivo): AnalisisDeExamenes
    {
        $lectora = ArchivoDeLectora::leer($archivo);

        $inscritos = Inscripcion::query()
            ->with('puesto:id_pue,codigo_pue,nombre_pue')
            ->delProceso($proceso->id_pro)
            ->orderBy('apellidos_nombres_ins')
            ->get(['id_ins', 'id_pue', 'documento_ins', 'apellidos_nombres_ins'])
            ->keyBy('documento_ins');

        if ($inscritos->isEmpty()) {
            throw new RuntimeException(
                "El proceso {$proceso->codigo_pro} no tiene inscritos. Importa primero las inscripciones: las hojas se cruzan por DNI."
            );
        }

        [$hojas, $errores, $presentes, $preguntas] = $this->validar($lectora, $inscritos->all());

        $anteriores = Examen::query()
            ->delProceso($proceso->id_pro)
            ->get(['id_ins', 'apellidos_nombres_exa', 'nombre_coincide_exa', 'puntaje_exa', 'aciertos_exa', 'errores_exa', 'blancos_exa', 'dobles_exa', 'respuestas_exa'])
            ->keyBy('id_ins');

        $nuevas = $actualizadas = $sinCambios = 0;
        $cambios = [];

        foreach ($hojas as $hoja) {
            $anterior = $anteriores->get($hoja['id_ins']);

            if ($anterior === null) {
                $nuevas++;
            } elseif (self::esLaMisma($anterior, $hoja)) {
                $sinCambios++;

                continue;
            } else {
                $actualizadas++;
            }

            $cambios[] = $hoja;
        }

        /* Quien no esta en el archivo ni tiene una hoja de una carga anterior. */
        $faltantes = [];

        foreach ($inscritos as $inscripcion) {
            if (! isset($presentes[$inscripcion->id_ins]) && ! $anteriores->has($inscripcion->id_ins)) {
                $faltantes[] = [
                    'documento' => $inscripcion->documento_ins,
                    'nombres' => $inscripcion->apellidos_nombres_ins,
                    'puesto' => $inscripcion->puesto->denominacion(),
                ];
            }
        }

        $porId = $inscritos->keyBy('id_ins');
        $nombresDistintos = [];

        foreach ($hojas as $hoja) {
            if (! $hoja['nombre_coincide_exa']) {
                $inscripcion = $porId->get($hoja['id_ins']);

                $nombresDistintos[] = [
                    'documento' => (string) $inscripcion?->documento_ins,
                    'padron' => (string) $inscripcion?->apellidos_nombres_ins,
                    'hoja' => (string) $hoja['apellidos_nombres_exa'],
                ];
            }
        }

        return new AnalisisDeExamenes(
            filas: count($lectora->hojas),
            preguntas: $preguntas,
            inscritos: $inscritos->count(),
            hojas: $hojas,
            cambios: $cambios,
            errores: $errores,
            nuevas: $nuevas,
            actualizadas: $actualizadas,
            sinCambios: $sinCambios,
            faltantes: $faltantes,
            nombresDistintos: $nombresDistintos,
        );
    }

    /**
     * @throws RuntimeException con un mensaje para el usuario cuando el archivo
     *                          no tiene el formato de la lectora o no se pudo guardar.
     */
    public function importar(Proceso $proceso, string $archivo, ?string $nombreArchivo = null, ?Usuario $usuario = null): ResultadoImportacion
    {
        $analisis = $this->analizar($proceso, $archivo);
        $nombreArchivo ??= basename($archivo);

        if (! $analisis->puedeImportarse()) {
            $resultado = ResultadoImportacion::rechazada($analisis->filas, $analisis->errores);
            $this->bitacora->registrar($proceso, TipoImportacion::Examenes, $archivo, $nombreArchivo, $resultado, $usuario);

            return $resultado;
        }

        $resultado = new ResultadoImportacion(
            count($analisis->hojas),
            $analisis->nuevas,
            $analisis->actualizadas,
            $analisis->sinCambios,
            aplicada: true,
            nota: self::verificacion($analisis),
        );

        try {
            DB::transaction(function () use ($proceso, $analisis, $archivo, $nombreArchivo, $usuario, $resultado): void {
                $importacion = $this->bitacora->registrar(
                    $proceso,
                    TipoImportacion::Examenes,
                    $archivo,
                    $nombreArchivo,
                    $resultado,
                    $usuario,
                );

                $ahora = now();

                foreach (array_chunk($analisis->cambios, self::TAMANO_LOTE) as $lote) {
                    Examen::upsert(
                        array_map(fn (array $hoja): array => $hoja + [
                            'id_pro' => $proceso->id_pro,
                            'id_imp' => $importacion->id_imp,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ], $lote),
                        ['id_ins'],
                        [
                            'id_imp', 'apellidos_nombres_exa', 'nombre_coincide_exa', 'puntaje_exa', 'aciertos_exa',
                            'errores_exa', 'blancos_exa', 'dobles_exa', 'respuestas_exa', 'updated_at',
                        ],
                    );
                }
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron guardar los exámenes. No se modificó la información anterior.');
        }

        return $resultado;
    }

    /**
     * Devuelve las hojas validas, las observaciones, los inscritos que estan
     * en el archivo (aunque su hoja tenga observaciones, para no contarlos
     * como faltantes) y el total de preguntas de la mayoria de las hojas.
     *
     * @param  array<string, Inscripcion>  $inscritos  por DNI
     * @return array{0: list<Hoja>, 1: list<string>, 2: array<int, true>, 3: ?int}
     */
    private function validar(ArchivoDeLectora $lectora, array $inscritos): array
    {
        $leidas = [];
        $lineaDelDni = [];
        $presentes = [];

        foreach ($lectora->hojas as $linea => $hoja) {
            $observaciones = [];
            $documento = (string) preg_replace('/[\s.\-]/', '', mb_strtoupper($hoja[ArchivoDeLectora::DOCUMENTO]));
            $nombres = mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $hoja[ArchivoDeLectora::NOMBRES])));
            $inscrito = null;

            /* Sin relleno de ceros: en la hoja un digito sin marcar no es un cero. */
            if ($documento === '') {
                $observaciones[] = 'la hoja no tiene DNI';
            } elseif (! AnexoPostulantes::documentoValido($documento)) {
                $observaciones[] = "el DNI «{$hoja[ArchivoDeLectora::DOCUMENTO]}» está incompleto o mal marcado";
            } elseif (isset($lineaDelDni[$documento])) {
                $observaciones[] = "el DNI {$documento} ya aparece en la línea {$lineaDelDni[$documento]}";
            } elseif (! isset($inscritos[$documento])) {
                $observaciones[] = "el DNI {$documento} no está inscrito en el proceso".($nombres === '' ? '' : " (en la hoja: {$nombres})");
            } else {
                $inscrito = $inscritos[$documento];
                $presentes[$inscrito->id_ins] = true;
            }

            $lineaDelDni[$documento] ??= $linea;

            foreach (self::CONTEOS as $campo => $titulo) {
                if (self::entero($hoja[$campo]) === null) {
                    $observaciones[] = "«{$hoja[$campo]}» en {$titulo} no es un número entero";
                }
            }

            $aciertos = self::entero($hoja[ArchivoDeLectora::ACIERTOS]);
            $errores = self::entero($hoja[ArchivoDeLectora::ERRORES]);
            $blancos = self::entero($hoja[ArchivoDeLectora::BLANCOS]);
            $dobles = self::entero($hoja[ArchivoDeLectora::DOBLES]);
            $nota = self::numero($hoja[ArchivoDeLectora::NOTA]);

            if ($nota === null) {
                $observaciones[] = "la nota «{$hoja[ArchivoDeLectora::NOTA]}» no es un número";
            } elseif ($aciertos !== null && abs($nota - $aciertos) > 0.0005) {
                $observaciones[] = "la nota ({$hoja[ArchivoDeLectora::NOTA]}) no es igual a los aciertos ({$aciertos})";
            }

            $total = $aciertos === null || $errores === null || $blancos === null || $dobles === null
                ? null
                : $aciertos + $errores + $blancos + $dobles;

            if ($total === 0) {
                $observaciones[] = 'la hoja no tiene ninguna pregunta calificada';
            }

            $registro = null;

            if ($observaciones === [] && $nota !== null
                && $aciertos !== null && $errores !== null && $blancos !== null && $dobles !== null) {
                $registro = [
                    'id_ins' => $inscrito->id_ins,
                    'apellidos_nombres_exa' => $nombres === '' ? null : mb_substr($nombres, 0, 200),
                    'nombre_coincide_exa' => $nombres === ''
                        || self::palabras($nombres) === self::palabras($inscrito->apellidos_nombres_ins),
                    'puntaje_exa' => number_format($nota, 3, '.', ''),
                    'aciertos_exa' => $aciertos,
                    'errores_exa' => $errores,
                    'blancos_exa' => $blancos,
                    'dobles_exa' => $dobles,
                    'respuestas_exa' => $lectora->traeRespuestas
                        ? self::respuestas($hoja[ArchivoDeLectora::RESPUESTAS], $aciertos + $errores + $blancos + $dobles)
                        : null,
                ];
            }

            $leidas[$linea] = ['observaciones' => $observaciones, 'total' => $total, 'registro' => $registro];
        }

        /* Segunda pasada: el total de preguntas se compara con el de la mayoria. */
        $preguntas = self::totalMasFrecuente(array_column($leidas, 'total'));
        $hojas = [];
        $errores = [];

        foreach ($leidas as $linea => $leida) {
            if ($leida['total'] !== null && $leida['total'] !== 0 && $leida['total'] !== $preguntas) {
                $leida['observaciones'][] = "aciertos, errores, blancos y dobles suman {$leida['total']} preguntas y en las demás hojas suman {$preguntas}";
            }

            if ($leida['observaciones'] !== []) {
                $errores[] = "Línea {$linea}: ".implode('; ', $leida['observaciones']).'.';
            } elseif ($leida['registro'] !== null) {
                $hojas[] = $leida['registro'];
            }
        }

        return [$hojas, $errores, $presentes, $preguntas];
    }

    /**
     * @param  Hoja  $hoja
     */
    private static function esLaMisma(Examen $anterior, array $hoja): bool
    {
        return $anterior->puntaje_exa === $hoja['puntaje_exa']
            && $anterior->aciertos_exa === $hoja['aciertos_exa']
            && $anterior->errores_exa === $hoja['errores_exa']
            && $anterior->blancos_exa === $hoja['blancos_exa']
            && $anterior->dobles_exa === $hoja['dobles_exa']
            && $anterior->respuestas_exa === $hoja['respuestas_exa']
            && $anterior->apellidos_nombres_exa === $hoja['apellidos_nombres_exa'];
    }

    private static function verificacion(AnalisisDeExamenes $analisis): string
    {
        $nota = 'Ya tienen examen '.number_format($analisis->conExamen()).' de '.number_format($analisis->inscritos).' inscritos.';
        $distintos = count($analisis->nombresDistintos);

        return $distintos === 0
            ? $nota
            : "{$nota} {$distintos} hoja(s) traen un nombre distinto al del padrón: revísalas con el filtro «Nombre distinto».";
    }

    private static function entero(string $valor): ?int
    {
        return preg_match('/^\d{1,4}$/', $valor) === 1 ? (int) $valor : null;
    }

    /**
     * Nota con coma o punto decimal: «30,00000» o «17.5».
     */
    private static function numero(string $valor): ?float
    {
        $valor = str_replace(',', '.', $valor);

        return preg_match('/^\d{1,4}(\.\d+)?$/', $valor) === 1 ? (float) $valor : null;
    }

    /**
     * Total de preguntas que tiene la mayoria de las hojas. Si hay empate gana
     * el que aparece primero en el archivo.
     *
     * @param  list<?int>  $totales
     */
    private static function totalMasFrecuente(array $totales): ?int
    {
        $frecuencias = array_count_values(array_filter($totales, fn (?int $total): bool => $total !== null && $total > 0));

        if ($frecuencias === []) {
            return null;
        }

        arsort($frecuencias);

        return (int) array_key_first($frecuencias);
    }

    /**
     * Solo las preguntas calificadas, una letra por pregunta: «-» en blanco y
     * «*» cuando hay mas de una marca.
     *
     * @param  list<string>  $marcas
     */
    private static function respuestas(array $marcas, int $preguntas): string
    {
        $respuestas = '';

        for ($pregunta = 0; $pregunta < $preguntas; $pregunta++) {
            $marca = mb_strtoupper(trim($marcas[$pregunta] ?? ''));

            $respuestas .= match (true) {
                $marca === '' => Examen::BLANCO,
                mb_strlen($marca) > 1 => Examen::DOBLE,
                default => $marca,
            };
        }

        return $respuestas;
    }

    /**
     * Palabras del nombre sin tildes, signos ni orden: «ACUÑA RIOS, ANA» y
     * «Ana Acuña Ríos» son el mismo nombre.
     */
    private static function palabras(string $nombre): string
    {
        $palabras = preg_split('/[^A-Z]+/', Str::upper(Str::ascii($nombre)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($palabras);

        return implode(' ', $palabras);
    }
}
