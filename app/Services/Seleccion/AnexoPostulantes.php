<?php

namespace App\Services\Seleccion;

use App\Services\Excel\LectorXlsx;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Lee el listado de postulantes en el formato del Anexo 06-A.
 *
 * El anexo no empieza en la fila 1: trae arriba el titulo del proceso, la
 * entidad y un parrafo de consideraciones, y la cabecera real aparece mas
 * abajo («Nº | APELLIDOS Y NOMBRES | CÓDIGO DE PUESTO | PUESTO ...»). Por eso
 * se busca la fila de cabecera en vez de asumir su posicion, y las columnas se
 * reconocen por su nombre normalizado —sin tildes, saltos de linea ni el texto
 * entre parentesis— para tolerar las variaciones de cada convocatoria.
 */
class AnexoPostulantes
{
    public const NUMERO = 'numero';

    public const DOCUMENTO = 'documento';

    public const NOMBRES = 'nombres';

    public const CODIGO_PUESTO = 'codigo_puesto';

    public const PUESTO = 'puesto';

    /**
     * Filas en las que se busca la cabecera antes de rendirse.
     */
    private const FILAS_DE_BUSQUEDA = 40;

    /**
     * @param  int  $filaCabecera  numero de fila de Excel donde esta la cabecera
     * @param  array<string, int>  $columnas  columna reconocida => indice desde A
     * @param  array<int, array<string, string>>  $filas  numero de fila => valores por columna reconocida
     */
    private function __construct(
        public readonly int $filaCabecera,
        public readonly array $columnas,
        public readonly array $filas,
    ) {}

    /**
     * @throws RuntimeException cuando el archivo no es un .xlsx legible o no
     *                          tiene una cabecera con el codigo de puesto.
     */
    public static function leer(string $archivo): self
    {
        try {
            return self::recorrer($archivo);
        } catch (RuntimeException $error) {
            throw $error;
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudo leer el archivo. Verifica que sea un Excel (.xlsx) sin daños.');
        }
    }

    private static function recorrer(string $archivo): self
    {
        try {
            $lector = new LectorXlsx($archivo);
        } catch (RuntimeException) {
            throw new RuntimeException('El archivo no es un Excel (.xlsx) válido.');
        }

        $hoja = $lector->hojas()[0] ?? throw new RuntimeException('El archivo no tiene ninguna hoja.');

        $filaCabecera = null;
        $columnas = [];
        $filas = [];

        foreach ($lector->filasCrudas($hoja) as $numero => $celdas) {
            if ($filaCabecera === null) {
                if ($numero > self::FILAS_DE_BUSQUEDA) {
                    break;
                }

                $reconocidas = self::reconocerCabecera($celdas);

                if (isset($reconocidas[self::CODIGO_PUESTO])) {
                    $filaCabecera = $numero;
                    $columnas = $reconocidas;
                }

                continue;
            }

            $valores = [];

            foreach ($columnas as $clave => $indice) {
                $valores[$clave] = trim((string) preg_replace('/\s+/u', ' ', $celdas[$indice] ?? ''));
            }

            if (array_filter($valores, fn (string $valor): bool => $valor !== '') !== []) {
                $filas[$numero] = $valores;
            }
        }

        if ($filaCabecera === null) {
            throw new RuntimeException(
                'No se encontró la fila de cabecera. El archivo debe tener la columna «CÓDIGO DE PUESTO», como el Anexo 06-A.'
            );
        }

        return new self($filaCabecera, $columnas, $filas);
    }

    public function tiene(string $columna): bool
    {
        return isset($this->columnas[$columna]);
    }

    /**
     * Codigo del puesto tal como se guarda. Excel convierte en numero una
     * celda como «00312» si no esta formateada como texto y se come los ceros
     * de la izquierda; se recuperan rellenando a los cinco digitos del cuadro
     * de puestos. El guion bajo aparece en algunos anexos («00306_1») y se
     * unifica con el guion.
     */
    public static function normalizarCodigo(string $codigo): string
    {
        $codigo = str_replace(['_', ' '], ['-', ''], mb_strtoupper(trim($codigo)));

        return ctype_digit($codigo) && strlen($codigo) < 5
            ? str_pad($codigo, 5, '0', STR_PAD_LEFT)
            : $codigo;
    }

    /**
     * Nombre del puesto sin el codigo que el anexo le agrega al final:
     * «ASISTENTE JUDICIAL (00340-1)» pasa a «ASISTENTE JUDICIAL».
     */
    public static function normalizarNombreDePuesto(string $puesto): string
    {
        $puesto = (string) preg_replace('/\s*\([^)]*\)\s*$/u', '', $puesto);

        return mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $puesto)));
    }

    /**
     * DNI o carnet de extranjeria sin separadores. Como con los codigos, un
     * DNI guardado como numero pierde los ceros de la izquierda y se rellena
     * a ocho digitos.
     */
    public static function normalizarDocumento(string $documento): string
    {
        $documento = (string) preg_replace('/[\s.\-]/', '', mb_strtoupper(trim($documento)));

        return ctype_digit($documento) && strlen($documento) < 8
            ? str_pad($documento, 8, '0', STR_PAD_LEFT)
            : $documento;
    }

    public static function documentoValido(string $documento): bool
    {
        return preg_match('/^(\d{8}|[0-9A-Z]{9,12})$/', $documento) === 1;
    }

    /**
     * @param  array<int, string>  $celdas
     * @return array<string, int>
     */
    private static function reconocerCabecera(array $celdas): array
    {
        $columnas = [];

        foreach ($celdas as $indice => $celda) {
            $clave = self::claveDeColumna($celda);

            if ($clave !== null && ! isset($columnas[$clave])) {
                $columnas[$clave] = $indice;
            }
        }

        return $columnas;
    }

    private static function claveDeColumna(string $titulo): ?string
    {
        $normalizado = (string) Str::of($titulo)
            ->replaceMatches('/\([^)]*\)/u', ' ')
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->squish();

        return match (true) {
            in_array($normalizado, ['CODIGO DE PUESTO', 'CODIGO PUESTO', 'COD PUESTO', 'CODIGO DEL PUESTO'], true) => self::CODIGO_PUESTO,
            in_array($normalizado, ['PUESTO', 'NOMBRE DEL PUESTO', 'DENOMINACION DEL PUESTO', 'CARGO'], true) => self::PUESTO,
            in_array($normalizado, ['APELLIDOS Y NOMBRES', 'APELLIDOS Y NOMBRE', 'NOMBRES Y APELLIDOS', 'POSTULANTE'], true) => self::NOMBRES,
            in_array($normalizado, ['N', 'NO', 'NRO', 'NUM', 'NUMERO', 'ITEM'], true) => self::NUMERO,
            preg_match('/\bDNI\b/', $normalizado) === 1, str_contains($normalizado, 'DOCUMENTO') => self::DOCUMENTO,
            default => null,
        };
    }
}
