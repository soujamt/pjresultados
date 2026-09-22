<?php

use App\Enums\Permiso;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Seleccion\ProcesoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * Modulo en construccion: aqui se generara el listado de resultados de la
 * evaluacion tecnica por puesto, con su orden de merito.
 */
new
#[Title('Resultados | Resultados PJ')]
class extends Component
{
    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'puesto', except: '')]
    public string $filtroPuesto = '';

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::ResultadosVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
    }

    public function updatedCodigoProceso(): void
    {
        $this->filtroPuesto = '';
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $proceso = $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::latest('id_pro')->get(['id_pro', 'codigo_pro']),
            'puestos' => $proceso === null
                ? collect()
                : Puesto::query()->delProceso($proceso->id_pro)->orderBy('nombre_pue')->orderBy('codigo_pue')->get(),
        ];
    }
};
