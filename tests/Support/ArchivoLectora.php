<?php

namespace Tests\Support;

/**
 * Arma un .txt con la forma del que exporta la lectora optica: separado por
 * punto y coma, en Windows-1252, con la nota con coma decimal y cien columnas
 * de respuestas despues de «RESPUESTAS», aunque el examen use menos.
 */
class ArchivoLectora
{
    public const CABECERA = 'NRO DE DNI;APELLIDOS Y NOMBRES;Nota 30;Aciertos;Errores;Blancos;Dobles;RESPUESTAS;';

    /**
     * @param  list<string>  $hojas  lineas armadas con hoja()
     */
    public static function archivo(array $hojas, string $cabecera = self::CABECERA, string $codificacion = 'Windows-1252'): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'lectora').'.txt';
        $contenido = implode("\r\n", [$cabecera, ...$hojas])."\r\n";

        file_put_contents($ruta, mb_convert_encoding($contenido, $codificacion, 'UTF-8'));

        return $ruta;
    }

    /**
     * Hoja de 30 preguntas: los aciertos se marcan con «A», los errores con
     * «B», los blancos quedan vacios y las dobles marcas llevan «AB».
     */
    public static function hoja(
        string $dni,
        string $nombres,
        int $aciertos,
        int $errores = 0,
        ?int $blancos = null,
        int $dobles = 0,
        ?string $nota = null,
    ): string {
        $blancos ??= 30 - $aciertos - $errores - $dobles;
        $nota ??= "{$aciertos},00000";

        $marcas = [
            ...array_fill(0, $aciertos, 'A'),
            ...array_fill(0, $errores, 'B'),
            ...array_fill(0, $blancos, ' '),
            ...array_fill(0, $dobles, 'AB'),
        ];

        return implode(';', [$dni, $nombres, $nota, $aciertos, $errores, $blancos, $dobles, ...array_pad($marcas, 100, ' ')]).';';
    }
}
