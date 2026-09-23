<?php

use App\Enums\Permiso;
use App\Models\Examen;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Evaluacion\ExamenService;
use App\Services\Evaluacion\ImportadorExamenes;
use App\Services\Seleccion\ProcesoService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/*
 * Carga de las hojas calificadas por la lectora optica y verificacion de que
 * cada inscrito tenga la suya.
 */
new
#[Title('Exámenes | Resultados PJ')]
class extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'unidad', except: '')]
    public string $filtroUnidad = '';

    #[Url(as: 'puesto', except: '')]
    public string $filtroPuesto = '';

    #[Url(as: 'estado', except: '')]
    public string $filtroEstado = '';

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    /** Archivo .txt que exporta la lectora optica. */
    public ?TemporaryUploadedFile $archivo = null;

    /**
     * @var ?array{filas: int, creados: int, actualizados: int, sin_cambios: int, errores: list<string>, aplicada: bool, nota: ?string, mensaje: string, archivo: string}
     */
    public ?array $ultimaImportacion = null;

    /**
     * Resumen del archivo elegido, antes de confirmar la carga.
     *
     * @var ?array{filas: int, preguntas: ?int, inscritos: int, validas: int, nuevas: int, actualizadas: int, sin_cambios: int, con_examen: int, puntajes: ?array{maximo: string, minimo: string, promedio: string}, errores: list<string>, total_errores: int, faltantes: list<array{documento: string, nombres: string, puesto: string}>, total_faltantes: int, nombres_distintos: list<array{documento: string, padron: string, hoja: string}>, total_nombres_distintos: int, importable: bool, archivo: string, cargado_antes: ?string}
     */
    public ?array $vistaPrevia = null;

    /** Hoja abierta en el modal de respuestas. */
    public ?int $hojaVisible = null;

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::ExamenesVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'codigoProceso') {
            $this->filtroUnidad = '';
            $this->filtroPuesto = '';
            $this->ultimaImportacion = null;
        }

        /* El puesto elegido puede no pertenecer a la nueva unidad. */
        if ($propiedad === 'filtroUnidad') {
            $this->filtroPuesto = '';
        }

        if (in_array($propiedad, ['codigoProceso', 'filtroUnidad', 'filtroPuesto', 'filtroEstado', 'busqueda'], true)) {
            $this->resetPage();
        }
    }

    public function filtrarPorEstado(string $estado): void
    {
        $this->filtroEstado = $this->filtroEstado === $estado ? '' : $estado;
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('filtroUnidad', 'filtroPuesto', 'filtroEstado', 'busqueda');
        $this->resetPage();
    }

    public function proceso(): ?Proceso
    {
        return $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
    }

    public function abrirImportacion(): void
    {
        $this->authorize(Permiso::ExamenesImportar->value);

        $this->reset('archivo', 'vistaPrevia');
        $this->resetValidation();

        Flux::modal('importar')->show();
    }

    /**
     * Apenas se elige el archivo se revisa completo, sin guardar nada, y se
     * muestra lo que pasaria al importarlo.
     */
    public function updatedArchivo(): void
    {
        $this->authorize(Permiso::ExamenesImportar->value);

        $this->vistaPrevia = null;
        $this->validarArchivo();
        $proceso = $this->procesoParaImportar();

        if ($proceso === null) {
            return;
        }

        try {
            $analisis = app(ImportadorExamenes::class)->analizar($proceso, $this->archivo->getRealPath());
        } catch (RuntimeException $error) {
            $this->addError('archivo', $error->getMessage());

            return;
        }

        $cargaAnterior = app(ExamenService::class)->cargaDelMismoArchivo($proceso, $this->archivo->getRealPath());

        $this->vistaPrevia = $analisis->resumen() + [
            'archivo' => $this->archivo->getClientOriginalName(),
            'cargado_antes' => $cargaAnterior?->created_at->format('d/m/Y \a \l\a\s H:i'),
        ];
    }

    public function quitarArchivo(): void
    {
        $this->reset('archivo', 'vistaPrevia');
        $this->resetValidation();
    }

    public function importar(ImportadorExamenes $importador): void
    {
        $this->authorize(Permiso::ExamenesImportar->value);

        $this->validarArchivo();
        $proceso = $this->procesoParaImportar();

        if ($proceso === null) {
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

        $this->ultimaImportacion = $resultado->toArray() + [
            'mensaje' => $resultado->mensaje('examen(es)'),
            'archivo' => $this->archivo->getClientOriginalName(),
        ];
        $this->reset('archivo', 'vistaPrevia');
        $this->resetPage();

        Flux::modal('importar')->close();
        Flux::toast(
            text: $resultado->mensaje('examen(es)'),
            variant: $resultado->aplicada ? 'success' : 'danger',
            duration: 10000,
        );
    }

    private function validarArchivo(): void
    {
        $this->validate([
            'archivo' => ['required', 'file', 'extensions:txt,csv', 'mimetypes:text/plain,text/csv,application/csv', 'max:5120'],
        ], [
            'archivo.required' => 'Elige el archivo .txt que exportó la lectora óptica.',
            'archivo.extensions' => 'Sube el archivo de texto (.txt) que exporta la lectora.',
            'archivo.mimetypes' => 'El archivo no es de texto. Sube el .txt tal como lo exporta la lectora.',
            'archivo.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);
    }

    private function procesoParaImportar(): ?Proceso
    {
        $proceso = $this->proceso();

        if ($proceso === null) {
            $this->addError('archivo', 'Elige primero el proceso al que pertenecen las hojas.');
        }

        return $proceso;
    }

    public function vaciar(ExamenService $servicio): void
    {
        $this->authorize(Permiso::ExamenesEliminar->value);

        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        $borrados = $servicio->vaciar($proceso);
        $this->ultimaImportacion = null;
        $this->resetPage();

        Flux::toast(text: "Se borraron {$borrados} examen(es) del proceso {$proceso->codigo_pro}.", variant: 'success');
    }

    public function verHoja(int $idExamen): void
    {
        $this->authorize(Permiso::ExamenesVer->value);

        $this->hojaVisible = $idExamen;

        Flux::modal('hoja')->show();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(ExamenService $servicio): array
    {
        $proceso = $this->proceso();
        $unidad = $this->filtroUnidad === '' ? null : (int) $this->filtroUnidad;
        $puesto = $this->filtroPuesto === '' ? null : (int) $this->filtroPuesto;

        return [
            'proceso' => $proceso,
            'procesos' => Proceso::latest('id_pro')->get(['id_pro', 'codigo_pro']),
            'unidades' => $proceso === null ? collect() : Unidad::query()
                ->whereHas('puestos', fn ($consulta) => $consulta->where('id_pro', $proceso->id_pro))
                ->orderBy('nombre_uni')
                ->get(['id_uni', 'nombre_uni']),
            'puestos' => $proceso === null ? collect() : Puesto::query()
                ->delProceso($proceso->id_pro)
                ->when($unidad !== null, fn ($consulta) => $consulta->deLaUnidad($unidad))
                ->orderBy('nombre_pue')
                ->orderBy('codigo_pue')
                ->get(),
            'resumen' => $proceso === null ? null : $servicio->resumen($proceso, $unidad, $puesto),
            'examenesDelProceso' => $proceso === null ? 0 : Examen::query()->delProceso($proceso->id_pro)->count(),
            'cargas' => $proceso === null ? collect() : $servicio->cargas($proceso),
            'inscripciones' => $servicio->consulta([
                'proceso' => $proceso?->id_pro,
                'unidad' => $unidad,
                'puesto' => $puesto,
                'busqueda' => $this->busqueda,
                'estado' => $this->filtroEstado,
            ])->paginate(50),
            'hoja' => $this->hojaVisible === null
                ? null
                : Examen::with(['inscripcion.puesto', 'importacion.usuario'])->find($this->hojaVisible),
        ];
    }
};
