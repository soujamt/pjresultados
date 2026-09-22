<?php

namespace Tests\Support;

/**
 * Arma un .xlsx con la forma del Anexo 06-A: titulos arriba, la cabecera en la
 * fila 12 y un pie de firma al final, igual que el archivo que publica la Corte.
 */
class Anexo06A
{
    /**
     * @param  list<list<string>>  $postulantes  filas debajo de la cabecera
     * @param  list<string>|null  $cabecera
     */
    public static function archivo(array $postulantes, ?array $cabecera = null): string
    {
        $cabecera ??= ['Nº', 'APELLIDOS Y NOMBRES', 'CÓDIGO DE PUESTO', "PUESTO\n(agrupado por puesto)", 'FECHA', 'HORA DE INGRESO'];

        $filas = [
            ['ANEXO N.º 06 - A'],
            [''],
            [''],
            ['PROCESO DE SELECCIÓN DE PERSONAL INDETERMINADO N° 002-2026-UE-UCAYALI'],
            ['CORTE SUPERIOR DE JUSTICIA DE UCAYALI'],
            [''],
            ['DECRETO LEGISLATIVO N° 728, A PLAZO INDETERMINADO'],
            ['LISTADO DE POSTULANTES QUE PARTICIPARÁN DE LA EVALUACIÓN TÉCNICA'],
            [''],
            ['Consideraciones necesarias: …'],
            [''],
            $cabecera,
            ...$postulantes,
            [''],
            ['Pucallpa, 11 de agosto del año 2026'],
        ];

        return (new ConstructorXlsx)->hoja('Técnica', $filas)->escribir();
    }

    /**
     * Fila del anexo publicado, sin DNI.
     *
     * @return list<string>
     */
    public static function fila(int $numero, string $nombres, string $codigo, string $puesto): array
    {
        return [(string) $numero, $nombres, $codigo, "{$puesto} ({$codigo})", '46291', '8:00 am. a 9:30 am.'];
    }

    /**
     * Cabecera del anexo con la columna DNI agregada.
     *
     * @return list<string>
     */
    public static function cabeceraConDni(): array
    {
        return ['Nº', 'DNI', 'APELLIDOS Y NOMBRES', 'CÓDIGO DE PUESTO', "PUESTO\n(agrupado por puesto)"];
    }

    /**
     * @return list<string>
     */
    public static function filaConDni(int $numero, string $dni, string $nombres, string $codigo, string $puesto = 'ASISTENTE JUDICIAL'): array
    {
        return [(string) $numero, $dni, $nombres, $codigo, "{$puesto} ({$codigo})"];
    }

    /**
     * Cabecera del listado completo que entrega la Corte («DATA GENERAL»):
     * empieza en la columna B y trae DNI, unidad de organizacion y aula.
     *
     * @return list<string>
     */
    public static function cabeceraCompleta(): array
    {
        return [
            '', 'Nº', "DOCUMENTO NACIONAL DE IDENTIDAD\n(DNI)", 'APELLIDOS Y NOMBRES', 'CÓDIGO DE PUESTO',
            "PUESTO\n(agrupado por puesto)", 'UNIDAD DE ORGANIZACIÓN', 'PABELLÓN', 'PISO', 'AULA', 'FIRMA',
        ];
    }

    /**
     * @return list<string>
     */
    public static function filaCompleta(int $numero, string $dni, string $nombres, string $codigo, string $puesto, string $unidad): array
    {
        return ['', (string) $numero, $dni, $nombres, $codigo, "{$puesto} ({$codigo})", $unidad];
    }
}
