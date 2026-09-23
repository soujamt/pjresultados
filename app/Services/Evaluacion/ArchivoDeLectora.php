<?php

namespace App\Services\Evaluacion;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Archivo de texto que exporta la lectora optica al calificar las hojas: una
 * linea por hoja y los campos separados por punto y coma.
 *
 *     NRO DE DNI;APELLIDOS Y NOMBRES;Nota 30;Aciertos;Errores;Blancos;Dobles;RESPUESTAS;
 *     71234567;GALVEZ DORADO, LUCIA;30,00000;30;0;0;0;A;B;C;…
 *
 * Desde «RESPUESTAS» sigue una columna por cada pregunta de la hoja, incluidas
 * las que el examen no usa. La lectora escribe en la codificacion de Windows
 * (la Ñ llega como un solo byte) y pone la nota con coma decimal.
 *
 * Aqui solo se leen los textos tal como vienen; ImportadorExamenes los valida.
 */
final class ArchivoDeLectora
{
    public const DOCUMENTO = 'documento';

    public const NOMBRES = 'nombres';

    public const NOTA = 'nota';

    public const ACIERTOS = 'aciertos';

    public const ERRORES = 'errores';

    public const BLANCOS = 'blancos';

    public const DOBLES = 'dobles';

    public const RESPUESTAS = 'respuestas';

    /**
     * Columnas sin las cuales no se puede calificar ni verificar una hoja.
     */
    private const OBLIGATORIAS = [
        self::DOCUMENTO => '«NRO DE DNI»',
        self::NOTA => '«Nota»',
        self::ACIERTOS => '«Aciertos»',
        self::ERRORES => '«Errores»',
        self::BLANCOS => '«Blancos»',
        self::DOBLES => '«Dobles»',
    ];

    /**
     * @param  array<int, array{documento: string, nombres: string, nota: string, aciertos: string, errores: string, blancos: string, dobles: string, respuestas: list<string>}>  $hojas  por numero de linea del archivo
     */
    private function __construct(
        public readonly array $hojas,
        public readonly bool $traeRespuestas,
    ) {}

    /**
     * @throws RuntimeException si el archivo no se puede leer o no tiene la
     *                          cabecera de la lectora.
     */
    public static function leer(string $ruta): self
    {
        $contenido = is_readable($ruta) ? file_get_contents($ruta) : false;

        if ($contenido === false) {
            throw new RuntimeException('No se pudo leer el archivo de la lectora.');
        }

        $lineas = preg_split('/\r\n|\r|\n/', self::aUtf8($contenido)) ?: [];
        $cabecera = null;
        $separador = ';';
        $columnas = [];
        $hojas = [];

        foreach ($lineas as $indice => $linea) {
            if (trim($linea) === '') {
                continue;
            }

            if ($cabecera === null) {
                $cabecera = $linea;
                $separador = self::separador($linea);
                $columnas = self::reconocerCabecera(self::campos($linea, $separador));
                self::exigirColumnas($columnas);

                continue;
            }

            $campos = self::campos($linea, $separador);

            /* Lineas con puros separadores, como las que deja una hoja de calculo. */
            if (implode('', array_map('trim', $campos)) === '') {
                continue;
            }

            $valor = fn (string $columna): string => isset($columnas[$columna])
                ? trim($campos[$columnas[$columna]] ?? '')
                : '';

            $hojas[$indice + 1] = [
                self::DOCUMENTO => $valor(self::DOCUMENTO),
                self::NOMBRES => $valor(self::NOMBRES),
                self::NOTA => $valor(self::NOTA),
                self::ACIERTOS => $valor(self::ACIERTOS),
                self::ERRORES => $valor(self::ERRORES),
                self::BLANCOS => $valor(self::BLANCOS),
                self::DOBLES => $valor(self::DOBLES),
                self::RESPUESTAS => isset($columnas[self::RESPUESTAS])
                    ? array_slice($campos, $columnas[self::RESPUESTAS])
                    : [],
            ];
        }

        if ($cabecera === null) {
            throw new RuntimeException('El archivo está vacío.');
        }

        return new self($hojas, isset($columnas[self::RESPUESTAS]));
    }

    /**
     * La lectora guarda en Windows-1252. Si el archivo ya viene en UTF-8 (por
     * ejemplo, porque alguien lo abrio y guardo en otro programa) se respeta.
     */
    private static function aUtf8(string $contenido): string
    {
        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        return mb_check_encoding($contenido, 'UTF-8')
            ? $contenido
            : mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }

    private static function separador(string $cabecera): string
    {
        foreach ([';', "\t", ','] as $separador) {
            if (str_contains($cabecera, $separador)) {
                return $separador;
            }
        }

        return ';';
    }

    /**
     * @return list<string>
     */
    private static function campos(string $linea, string $separador): array
    {
        return array_map(strval(...), str_getcsv($linea, $separador, '"', ''));
    }

    /**
     * Ubica cada columna por su titulo, sin importar mayusculas, tildes ni el
     * numero que acompaña a «Nota» (la escala de la calificacion).
     *
     * @param  list<string>  $titulos
     * @return array<string, int>
     */
    private static function reconocerCabecera(array $titulos): array
    {
        $columnas = [];

        foreach ($titulos as $indice => $titulo) {
            $clave = trim((string) preg_replace('/[^A-Z0-9]+/', ' ', Str::upper(Str::ascii($titulo))));

            $columna = match (true) {
                $clave === '' => null,
                str_contains($clave, 'DNI'), str_starts_with($clave, 'DOCUMENTO') => self::DOCUMENTO,
                str_contains($clave, 'NOMBRE') => self::NOMBRES,
                str_starts_with($clave, 'NOTA'), str_starts_with($clave, 'PUNTAJE') => self::NOTA,
                str_starts_with($clave, 'ACIERTO') => self::ACIERTOS,
                str_starts_with($clave, 'ERROR') => self::ERRORES,
                str_starts_with($clave, 'BLANCO') => self::BLANCOS,
                str_starts_with($clave, 'DOBLE') => self::DOBLES,
                str_starts_with($clave, 'RESPUESTA') => self::RESPUESTAS,
                default => null,
            };

            if ($columna !== null && ! isset($columnas[$columna])) {
                $columnas[$columna] = $indice;
            }
        }

        return $columnas;
    }

    /**
     * @param  array<string, int>  $columnas
     */
    private static function exigirColumnas(array $columnas): void
    {
        $faltan = array_diff_key(self::OBLIGATORIAS, $columnas);

        if ($faltan !== []) {
            throw new RuntimeException(
                'El archivo no tiene la cabecera de la lectora óptica: faltan las columnas '.implode(', ', $faltan).'.'
            );
        }
    }
}
