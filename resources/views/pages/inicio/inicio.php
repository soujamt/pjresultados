<?php

use App\Models\Inscripcion;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Seleccion\ProcesoService;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Inicio | Resultados PJ')]
class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(ProcesoService $procesos): array
    {
        $proceso = $procesos->vigente();

        return [
            'proceso' => $proceso,
            'puestos' => $proceso ? Puesto::query()->delProceso($proceso->id_pro)->count() : 0,
            'unidades' => $proceso
                ? Unidad::query()->whereHas('puestos', fn ($consulta) => $consulta->where('id_pro', $proceso->id_pro))->count()
                : 0,
            'inscritos' => $proceso ? Inscripcion::query()->delProceso($proceso->id_pro)->count() : 0,
        ];
    }
};
