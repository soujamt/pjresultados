<?php

namespace App\Services\Seleccion;

/**
 * Resumen inmutable de una carga de archivo. Cuando hay errores la carga no se
 * aplica: `aplicada` queda en falso y los contadores en cero.
 */
final readonly class ResultadoImportacion
{
    /**
     * @param  list<string>  $errores
     */
    public function __construct(
        public int $filas,
        public int $creados = 0,
        public int $actualizados = 0,
        public int $sinCambios = 0,
        public array $errores = [],
        public bool $aplicada = false,
    ) {}

    /**
     * @param  list<string>  $errores
     */
    public static function rechazada(int $filas, array $errores): self
    {
        return new self(filas: $filas, errores: $errores);
    }

    public function tieneErrores(): bool
    {
        return $this->errores !== [];
    }

    public function mensaje(string $registros): string
    {
        if (! $this->aplicada) {
            return $this->filas === 0 && ! $this->tieneErrores()
                ? 'El archivo no tiene filas con datos debajo de la cabecera.'
                : 'No se cargó nada: el archivo tiene '.count($this->errores).' observación(es). Corrígelas y vuelve a subirlo.';
        }

        return "Se leyeron {$this->filas} fila(s): {$this->creados} {$registros} nuevo(s), "
            ."{$this->actualizados} actualizado(s) y {$this->sinCambios} sin cambios.";
    }

    /**
     * @return array{filas: int, creados: int, actualizados: int, sin_cambios: int, errores: list<string>, aplicada: bool}
     */
    public function toArray(): array
    {
        return [
            'filas' => $this->filas,
            'creados' => $this->creados,
            'actualizados' => $this->actualizados,
            'sin_cambios' => $this->sinCambios,
            'errores' => $this->errores,
            'aplicada' => $this->aplicada,
        ];
    }
}
