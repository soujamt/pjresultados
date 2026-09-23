<?php

use App\Enums\Permiso;
use App\Livewire\Forms\PublicacionForm;
use App\Models\Descalificacion;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Evaluacion\ResultadoDePuesto;
use App\Services\Evaluacion\ResultadoService;
use App\Services\Seleccion\ProcesoService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/*
 * Resultados de la evaluacion tecnica por puesto, en el formato del Anexo 07.
 * Se calculan al momento con los examenes cargados; aqui se registran las
 * descalificaciones y los datos del pie, y se descarga el PDF o el Excel.
 */
new
#[Title('Resultados | Resultados PJ')]
class extends Component
{
    /** Motivo elegido en el modal cuando no es ninguno de la lista. */
    private const OTRO_MOTIVO = 'otro';

    #[Url(as: 'proceso', except: '')]
    public string $codigoProceso = '';

    #[Url(as: 'unidad', except: '')]
    public string $filtroUnidad = '';

    #[Url(as: 'puesto', except: '')]
    public string $filtroPuesto = '';

    public PublicacionForm $publicacion;

    /** Inscripcion que se esta descalificando en el modal. */
    public ?int $idInscripcion = null;

    public string $motivo = '';

    public string $otroMotivo = '';

    public function mount(ProcesoService $procesos): void
    {
        $this->authorize(Permiso::ResultadosVer->value);

        if ($this->codigoProceso === '') {
            $this->codigoProceso = $procesos->vigente()?->codigo_pro ?? '';
        }
    }

    public function updated(string $propiedad): void
    {
        if ($propiedad === 'codigoProceso') {
            $this->reset('filtroUnidad', 'filtroPuesto');
        }

        /* El puesto elegido puede no pertenecer a la nueva unidad. */
        if ($propiedad === 'filtroUnidad') {
            $this->filtroPuesto = '';
        }
    }

    public function proceso(): ?Proceso
    {
        return $this->codigoProceso === ''
            ? null
            : Proceso::where('codigo_pro', $this->codigoProceso)->first();
    }

    public function verPuesto(int $idPuesto): void
    {
        $this->filtroPuesto = (string) $idPuesto;
    }

    public function verTodos(): void
    {
        $this->filtroPuesto = '';
    }

    public function abrirPublicacion(): void
    {
        $this->authorize(Permiso::ResultadosGenerar->value);

        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        $this->publicacion->resetValidation();
        $this->publicacion->llenar($proceso);

        Flux::modal('publicacion')->show();
    }

    public function guardarPublicacion(ResultadoService $servicio): void
    {
        $this->authorize(Permiso::ResultadosGenerar->value);

        $this->publicacion->validate();
        $proceso = $this->proceso();

        if ($proceso === null) {
            return;
        }

        $servicio->guardarPublicacion($proceso, $this->publicacion->datos());

        Flux::modal('publicacion')->close();
        Flux::toast(text: 'Se guardaron los datos de la publicación.', variant: 'success');
    }

    /**
     * El modal lo abre el navegador y deja el postulante en $idInscripcion;
     * aqui llegan juntos el postulante y el motivo.
     */
    public function descalificar(ResultadoService $servicio): void
    {
        $this->authorize(Permiso::ResultadosGenerar->value);

        $this->validate([
            'idInscripcion' => ['required', 'integer'],
            'motivo' => ['required', Rule::in([...Descalificacion::MOTIVOS, self::OTRO_MOTIVO])],
            'otroMotivo' => ['required_if:motivo,'.self::OTRO_MOTIVO, 'nullable', 'string', 'max:255'],
        ], [
            'motivo.required' => 'Elige el motivo de la descalificación.',
            'otroMotivo.required_if' => 'Escribe el motivo tal como se publicará.',
        ]);

        $inscripcion = $this->inscripcionDelProceso($this->idInscripcion);
        $motivo = $this->motivo === self::OTRO_MOTIVO ? $this->otroMotivo : $this->motivo;

        $servicio->descalificar($inscripcion, $motivo, auth()->user());

        $this->reset('idInscripcion', 'motivo', 'otroMotivo');
        Flux::modal('descalificar')->close();
        Flux::toast(text: "{$inscripcion->apellidos_nombres_ins} quedó descalificado/a.", variant: 'success');
    }

    public function quitarDescalificacion(int $idInscripcion, ResultadoService $servicio): void
    {
        $this->authorize(Permiso::ResultadosGenerar->value);

        $inscripcion = $this->inscripcionDelProceso($idInscripcion);
        $servicio->quitarDescalificacion($inscripcion);

        Flux::toast(text: "Se quitó la descalificación de {$inscripcion->apellidos_nombres_ins}.", variant: 'success');
    }

    private function inscripcionDelProceso(?int $idInscripcion): Inscripcion
    {
        return Inscripcion::query()
            ->delProceso($this->proceso()->id_pro ?? 0)
            ->findOrFail($idInscripcion);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(ResultadoService $servicio): array
    {
        $proceso = $this->proceso();
        $unidad = $this->filtroUnidad === '' ? null : (int) $this->filtroUnidad;
        $puesto = $this->filtroPuesto === '' ? null : (int) $this->filtroPuesto;
        $resultados = $proceso === null ? [] : $servicio->porPuesto($proceso, $unidad, $puesto);

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
            'resultados' => $resultados,
            'totales' => [
                'inscritos' => array_sum(array_map(fn (ResultadoDePuesto $delPuesto): int => $delPuesto->inscritos(), $resultados)),
                'aptos' => array_sum(array_map(fn (ResultadoDePuesto $delPuesto): int => $delPuesto->aptos(), $resultados)),
                'desaprobados' => array_sum(array_map(fn (ResultadoDePuesto $delPuesto): int => $delPuesto->desaprobados(), $resultados)),
                'ausentes' => array_sum(array_map(fn (ResultadoDePuesto $delPuesto): int => $delPuesto->ausentes(), $resultados)),
                'descalificados' => array_sum(array_map(fn (ResultadoDePuesto $delPuesto): int => $delPuesto->descalificados(), $resultados)),
            ],
            'hayExamenes' => $proceso !== null && Examen::query()->delProceso($proceso->id_pro)->exists(),
            'motivos' => Descalificacion::MOTIVOS,
            'opcionOtroMotivo' => self::OTRO_MOTIVO,
        ];
    }
};
