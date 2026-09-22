<?php

namespace App\Services\Seleccion;

use App\Models\Unidad;

/**
 * Puestos que aparecen en un archivo, reunidos por codigo.
 *
 * El anexo trae una fila por postulante, asi que cada puesto se repite tantas
 * veces como inscritos tenga. Aqui se junta la informacion de todas esas filas
 * y se detecta cuando se contradicen: un mismo codigo con dos nombres o en dos
 * unidades de organizacion es un error de digitacion.
 */
final class PuestosDelArchivo
{
    /** @var array<string, array{nombre: string, unidad: ?string, fila: int}> */
    private array $puestos = [];

    /**
     * Anota el puesto de una fila. Devuelve la observacion si contradice lo
     * que ya se vio en una fila anterior, o null si es coherente.
     *
     * @param  string  $nombre  vacio cuando el archivo no trae la columna
     * @param  ?string  $unidad  null cuando el archivo no la trae
     */
    public function anotar(int $fila, string $codigo, string $nombre, ?string $unidad): ?string
    {
        $anterior = $this->puestos[$codigo] ?? null;

        if ($anterior === null) {
            $this->puestos[$codigo] = ['nombre' => $nombre, 'unidad' => $unidad, 'fila' => $fila];

            return null;
        }

        if ($nombre !== '' && $anterior['nombre'] !== '' && $nombre !== $anterior['nombre']) {
            return "el código {$codigo} aparece como «{$nombre}», pero en la fila {$anterior['fila']} figura como «{$anterior['nombre']}»";
        }

        if ($unidad !== null && $anterior['unidad'] !== null
            && Unidad::claveDeNombre($unidad) !== Unidad::claveDeNombre($anterior['unidad'])) {
            return "el puesto {$codigo} aparece en «{$unidad}», pero en la fila {$anterior['fila']} figura en «{$anterior['unidad']}»";
        }

        if ($anterior['nombre'] === '') {
            $this->puestos[$codigo]['nombre'] = $nombre;
        }

        $this->puestos[$codigo]['unidad'] ??= $unidad;

        return null;
    }

    public function vacio(): bool
    {
        return $this->puestos === [];
    }

    /**
     * @return array<string, array{nombre: string, unidad: ?string, fila: int}>
     */
    public function todos(): array
    {
        return $this->puestos;
    }
}
