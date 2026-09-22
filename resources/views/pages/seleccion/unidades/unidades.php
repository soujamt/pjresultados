<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\UnidadForm;
use App\Models\Proceso;
use App\Models\Unidad;
use App\Services\Seleccion\ProcesoService;
use App\Services\Seleccion\UnidadService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Title('Unidades de organización | Resultados PJ')]
class extends Component
{
    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    public UnidadForm $form;

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::UnidadesVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
    }

    public function nuevo(): void
    {
        $this->authorize(Permiso::UnidadesCrear->value);

        $this->form->reset();
        $this->resetValidation();

        Flux::modal('unidad')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::UnidadesEditar->value);

        $this->form->llenar(Unidad::findOrFail($id));
        $this->resetValidation();

        Flux::modal('unidad')->show();
    }

    public function guardar(UnidadService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::UnidadesCrear->value
            : Permiso::UnidadesEditar->value);

        $this->form->normalizar();
        $this->form->validate();

        $unidad = $this->form->id === null ? null : Unidad::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $unidad);

        Flux::modal('unidad')->close();
        $this->form->reset();

        Flux::toast(text: 'La unidad de organización fue guardada.', variant: 'success');
    }

    public function alternarEstado(int $id, UnidadService $servicio): void
    {
        $this->authorize(Permiso::UnidadesEditar->value);

        $servicio->alternarEstado(Unidad::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, UnidadService $servicio): void
    {
        $this->authorize(Permiso::UnidadesEliminar->value);

        try {
            $servicio->eliminar(Unidad::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'La unidad de organización fue eliminada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $proceso = $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
        $idProceso = $proceso->id_pro ?? 0;
        $busqueda = trim($this->busqueda);

        /*
         * Las unidades son de la Corte, pero los puestos y los inscritos que se
         * muestran son solo los del proceso elegido.
         */
        $unidades = Unidad::query()
            ->with(['puestos' => fn ($consulta) => $consulta
                ->where('id_pro', $idProceso)
                ->withCount('inscripciones')
                ->orderBy('codigo_pue')])
            ->withCount([
                'puestos' => fn (Builder $consulta) => $consulta->where('id_pro', $idProceso),
                'inscripciones' => fn (Builder $consulta) => $consulta->where('tbl_inscripcion.id_pro', $idProceso),
            ])
            ->when($busqueda !== '', fn (Builder $consulta) => $consulta->where('nombre_uni', 'like', '%'.mb_strtoupper($busqueda).'%'))
            ->orderBy('nombre_uni')
            ->get();

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::latest('id_pro')->get(['id_pro', 'codigo_pro']),
            'unidades' => $unidades,
            'totalInscritos' => $unidades->sum('inscripciones_count'),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
