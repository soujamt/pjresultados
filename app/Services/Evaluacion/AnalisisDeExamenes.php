<?php

namespace App\Services\Evaluacion;

/**
 * Lo que pasaria si se importara un archivo de la lectora, sin haber escrito
 * nada todavia. Es la vista previa que se muestra antes de confirmar la carga
 * y lo que despues usa ImportadorExamenes para guardar.
 *
 * @phpstan-type Hoja array{id_ins: int, apellidos_nombres_exa: ?string, nombre_coincide_exa: bool, puntaje_exa: string, aciertos_exa: int, errores_exa: int, blancos_exa: int, dobles_exa: int, respuestas_exa: ?string}
 * @phpstan-type Postulante array{documento: string, nombres: string, puesto: string}
 * @phpstan-type NombreDistinto array{documento: string, padron: string, hoja: string}
 */
final readonly class AnalisisDeExamenes
{
    /**
     * @param  list<Hoja>  $hojas  todas las hojas validas del archivo
     * @param  list<Hoja>  $cambios  las que son nuevas o cambiaron: lo unico que se escribe
     * @param  list<string>  $errores
     * @param  list<Postulante>  $faltantes  inscritos que se quedarian sin examen
     * @param  list<NombreDistinto>  $nombresDistintos
     */
    public function __construct(
        public int $filas,
        public ?int $preguntas,
        public int $inscritos,
        public array $hojas,
        public array $cambios,
        public array $errores,
        public int $nuevas,
        public int $actualizadas,
        public int $sinCambios,
        public array $faltantes,
        public array $nombresDistintos,
    ) {}

    public function puedeImportarse(): bool
    {
        return $this->errores === [] && $this->hojas !== [];
    }

    /**
     * Inscritos que tendran examen despues de la carga, sumando las anteriores.
     */
    public function conExamen(): int
    {
        return $this->inscritos - count($this->faltantes);
    }

    /**
     * @return ?array{maximo: string, minimo: string, promedio: string}
     */
    public function puntajes(): ?array
    {
        if ($this->hojas === []) {
            return null;
        }

        $puntajes = array_map(fn (array $hoja): float => (float) $hoja['puntaje_exa'], $this->hojas);
        $formato = fn (float $valor): string => rtrim(rtrim(number_format($valor, 2, ',', ''), '0'), ',');

        return [
            'maximo' => $formato(max($puntajes)),
            'minimo' => $formato(min($puntajes)),
            'promedio' => $formato(array_sum($puntajes) / count($puntajes)),
        ];
    }

    /**
     * Version para la pantalla. Las listas se recortan para no mandar al
     * navegador cientos de filas cuando el archivo esta muy incompleto.
     *
     * @return array{filas: int, preguntas: ?int, inscritos: int, validas: int, nuevas: int, actualizadas: int, sin_cambios: int, con_examen: int, puntajes: ?array{maximo: string, minimo: string, promedio: string}, errores: list<string>, total_errores: int, faltantes: list<Postulante>, total_faltantes: int, nombres_distintos: list<NombreDistinto>, total_nombres_distintos: int, importable: bool}
     */
    public function resumen(int $limite = 200): array
    {
        return [
            'filas' => $this->filas,
            'preguntas' => $this->preguntas,
            'inscritos' => $this->inscritos,
            'validas' => count($this->hojas),
            'nuevas' => $this->nuevas,
            'actualizadas' => $this->actualizadas,
            'sin_cambios' => $this->sinCambios,
            'con_examen' => $this->conExamen(),
            'puntajes' => $this->puntajes(),
            'errores' => array_slice($this->errores, 0, $limite),
            'total_errores' => count($this->errores),
            'faltantes' => array_slice($this->faltantes, 0, $limite),
            'total_faltantes' => count($this->faltantes),
            'nombres_distintos' => array_slice($this->nombresDistintos, 0, $limite),
            'total_nombres_distintos' => count($this->nombresDistintos),
            'importable' => $this->puedeImportarse(),
        ];
    }
}
