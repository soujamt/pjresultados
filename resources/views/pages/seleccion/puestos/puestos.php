<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Enums\TipoImportacion;
use App\Livewire\Forms\PuestoForm;
use App\Models\Importacion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Seleccion\ImportadorPuestos;
use App\Services\Seleccion\ProcesoService;
use App\Services\Seleccion\PuestoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new
#[Title('Puestos | Resultados PJ')]
class extends Component
{
    use WithFileUploads;

    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    public PuestoForm $form;

    /** Excel del Anexo 06-A que se esta subiendo. */
    public ?TemporaryUploadedFile $archivo = null;

    /**
     * Resumen de la ultima carga, para listar las observaciones tras cerrar
     * el modal.
     *
     * @var ?array{filas: int, creados: int, actualizados: int, sin_cambios: int, errores: list<string>, aplicada: bool, mensaje: string}
     */
    public ?array $ultimaImportacion = null;

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::PuestosVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
    }

    public function updatedCodigoProceso(): void
    {
        $this->ultimaImportacion = null;
    }

    public function proceso(): ?Proceso
    {
        return $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
    }

    public function nuevo(): void
    {
        $this->authorize(Permiso::PuestosCrear->value);

        $this->form->reset();
        $this->form->proceso = $this->proceso()?->id_pro;
        $this->resetValidation();

        Flux::modal('puesto')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::PuestosEditar->value);

        $this->form->llenar(Puesto::findOrFail($id));
        $this->resetValidation();

        Flux::modal('puesto')->show();
    }

    public function guardar(PuestoService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::PuestosCrear->value
            : Permiso::PuestosEditar->value);

        $this->form->normalizar();
        $this->form->validate();

        $puesto = $this->form->id === null ? null : Puesto::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $puesto);

        Flux::modal('puesto')->close();
        $this->form->reset();

        Flux::toast(text: 'El puesto fue guardado.', variant: 'success');
    }

    public function alternarEstado(int $id, PuestoService $servicio): void
    {
        $this->authorize(Permiso::PuestosEditar->value);

        $servicio->alternarEstado(Puesto::findOrFail($id));

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, PuestoService $servicio): void
    {
        $this->authorize(Permiso::PuestosEliminar->value);

        try {
            $servicio->eliminar(Puesto::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El puesto fue eliminado.', variant: 'success');
    }

    public function abrirImportacion(): void
    {
        $this->authorize(Permiso::PuestosImportar->value);

        $this->reset('archivo');
        $this->resetValidation();

        Flux::modal('importar')->show();
    }

    public function importar(ImportadorPuestos $importador): void
    {
        $this->authorize(Permiso::PuestosImportar->value);

        $this->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'archivo.required' => 'Elige el archivo Excel del Anexo 06-A.',
            'archivo.mimes' => 'Sube el archivo en formato Excel (.xlsx).',
            'archivo.max' => 'El archivo no puede pesar más de 10 MB.',
        ]);

        $proceso = $this->proceso();

        if ($proceso === null) {
            $this->addError('archivo', 'Elige primero el proceso al que pertenecen los puestos.');

            return;
        }

        try {
            $resultado = $importador->importar(
                $proceso,
                $this->archivo->getRealPath(),
                $this->archivo->getClientOriginalName(),
                auth()->user(),
            );
        } catch (RuntimeException $error) {
            $this->addError('archivo', $error->getMessage());

            return;
        }

        $this->ultimaImportacion = $resultado->toArray() + ['mensaje' => $resultado->mensaje('puesto(s)')];
        $this->reset('archivo');

        Flux::modal('importar')->close();
        Flux::toast(
            text: $resultado->mensaje('puesto(s)'),
            variant: $resultado->aplicada ? 'success' : 'danger',
            duration: 8000,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $proceso = $this->proceso();
        $busqueda = trim($this->busqueda);

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::latest('id_pro')->get(['id_pro', 'codigo_pro']),
            'puestos' => $proceso === null ? collect() : Puesto::query()
                ->delProceso($proceso->id_pro)
                ->withCount('inscripciones')
                ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($consulta) use ($busqueda): void {
                    $consulta->where('codigo_pue', 'like', "%{$busqueda}%")
                        ->orWhere('nombre_pue', 'like', '%'.mb_strtoupper($busqueda).'%');
                }))
                ->orderBy('nombre_pue')
                ->orderBy('codigo_pue')
                ->get(),
            'ultimaCarga' => $proceso === null ? null : Importacion::query()
                ->where('id_pro', $proceso->id_pro)
                ->where('tipo_imp', TipoImportacion::Puestos)
                ->where('aplicada_imp', true)
                ->with('usuario')
                ->latest('id_imp')
                ->first(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
