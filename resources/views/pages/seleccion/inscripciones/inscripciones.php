<?php

use App\Enums\Permiso;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Seleccion\ImportadorInscripciones;
use App\Services\Seleccion\InscripcionService;
use App\Services\Seleccion\ProcesoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Title('Inscripciones | Resultados PJ')]
class extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'puesto', except: '')]
    public string $filtroPuesto = '';

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    /** Excel del Anexo 06-A con la columna DNI. */
    public ?TemporaryUploadedFile $archivo = null;

    /**
     * @var ?array{filas: int, creados: int, actualizados: int, sin_cambios: int, errores: list<string>, aplicada: bool, mensaje: string}
     */
    public ?array $ultimaImportacion = null;

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::InscripcionesVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'codigoProceso') {
            $this->filtroPuesto = '';
            $this->ultimaImportacion = null;
        }

        if (in_array($propiedad, ['codigoProceso', 'filtroPuesto', 'busqueda'], true)) {
            $this->resetPage();
        }
    }

    public function proceso(): ?Proceso
    {
        return $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
    }

    public function abrirImportacion(): void
    {
        $this->authorize(Permiso::InscripcionesImportar->value);

        $this->reset('archivo');
        $this->resetValidation();

        Flux::modal('importar')->show();
    }

    public function importar(ImportadorInscripciones $importador): void
    {
        $this->authorize(Permiso::InscripcionesImportar->value);

        $this->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'archivo.required' => 'Elige el archivo Excel con el listado de postulantes.',
            'archivo.mimes' => 'Sube el archivo en formato Excel (.xlsx).',
            'archivo.max' => 'El archivo no puede pesar más de 10 MB.',
        ]);

        $proceso = $this->proceso();

        if ($proceso === null) {
            $this->addError('archivo', 'Elige primero el proceso al que pertenecen los postulantes.');

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

        $this->ultimaImportacion = $resultado->toArray() + ['mensaje' => $resultado->mensaje('inscrito(s)')];
        $this->reset('archivo');
        $this->resetPage();

        Flux::modal('importar')->close();
        Flux::toast(
            text: $resultado->mensaje('inscrito(s)'),
            variant: $resultado->aplicada ? 'success' : 'danger',
            duration: 8000,
        );
    }

    public function eliminar(int $id, InscripcionService $servicio): void
    {
        $this->authorize(Permiso::InscripcionesEliminar->value);

        $servicio->eliminar(Inscripcion::findOrFail($id));

        Flux::toast(text: 'La inscripción fue eliminada.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(InscripcionService $servicio): array
    {
        $proceso = $this->proceso();

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::latest('id_pro')->get(['id_pro', 'codigo_pro']),
            'puestos' => $proceso === null
                ? collect()
                : Puesto::query()->delProceso($proceso->id_pro)->orderBy('nombre_pue')->orderBy('codigo_pue')->get(),
            'inscripciones' => $servicio->consulta([
                'proceso' => $proceso?->id_pro,
                'puesto' => $this->filtroPuesto === '' ? null : (int) $this->filtroPuesto,
                'busqueda' => $this->busqueda,
            ])->paginate(50),
        ];
    }
};
