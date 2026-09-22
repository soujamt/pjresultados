<?php

use App\Enums\Permiso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Services\Seleccion\ProcesoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * Modulo en construccion: aqui se cargaran los archivos de la lectora de
 * examenes (padron y respuestas) de la evaluacion tecnica.
 */
new
#[Title('Exámenes | Resultados PJ')]
class extends Component
{
    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::ExamenesVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
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
            'inscritos' => $proceso ? Inscripcion::query()->delProceso($proceso->id_pro)->count() : 0,
        ];
    }
};
