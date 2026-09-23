<?php

namespace App\Services\Evaluacion;

use App\Enums\TipoImportacion;
use App\Models\Examen;
use App\Models\Importacion;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Services\Seleccion\InscripcionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExamenService
{
    /** Filtros por la situacion de la hoja de cada inscrito. */
    public const CON_EXAMEN = 'con';

    public const SIN_EXAMEN = 'sin';

    public const NOMBRE_DISTINTO = 'nombre';

    public function __construct(private readonly InscripcionService $inscripciones) {}

    /**
     * Inscritos del proceso con su hoja, si la tienen. Se parte de las
     * inscripciones para que tambien se vea quien aun no tiene examen.
     *
     * @param  array{proceso: ?int, unidad: ?int, puesto: ?int, busqueda: string, estado: string}  $filtros
     * @return Builder<Inscripcion>
     */
    public function consulta(array $filtros): Builder
    {
        return $this->inscripciones
            ->consulta([
                'proceso' => $filtros['proceso'],
                'unidad' => $filtros['unidad'],
                'puesto' => $filtros['puesto'],
                'busqueda' => $filtros['busqueda'],
            ])
            ->with('examen')
            ->when($filtros['estado'] === self::CON_EXAMEN, fn (Builder $consulta) => $consulta->has('examen'))
            ->when($filtros['estado'] === self::SIN_EXAMEN, fn (Builder $consulta) => $consulta->doesntHave('examen'))
            ->when($filtros['estado'] === self::NOMBRE_DISTINTO, fn (Builder $consulta) => $consulta->whereHas(
                'examen',
                fn (Builder $examen) => $examen->where('nombre_coincide_exa', false),
            ));
    }

    /**
     * Cifras para verificar la carga, en una sola consulta. Respetan la unidad
     * y el puesto elegidos, igual que el listado.
     *
     * @return array{inscritos: int, con_examen: int, sin_examen: int, nombre_distinto: int}
     */
    public function resumen(Proceso $proceso, ?int $unidad = null, ?int $puesto = null): array
    {
        $fila = Inscripcion::query()
            ->leftJoin('tbl_examen', 'tbl_examen.id_ins', '=', 'tbl_inscripcion.id_ins')
            ->where('tbl_inscripcion.id_pro', $proceso->id_pro)
            ->when($unidad !== null, fn (Builder $consulta) => $consulta->whereIn(
                'tbl_inscripcion.id_pue',
                Puesto::query()->select('id_pue')->deLaUnidad($unidad),
            ))
            ->when($puesto !== null, fn (Builder $consulta) => $consulta->where('tbl_inscripcion.id_pue', $puesto))
            ->toBase()
            ->selectRaw('count(*) as inscritos')
            ->selectRaw('count(tbl_examen.id_exa) as con_examen')
            ->selectRaw('sum(case when tbl_examen.nombre_coincide_exa = ? then 1 else 0 end) as nombre_distinto', [false])
            ->first();

        $inscritos = (int) ($fila->inscritos ?? 0);
        $conExamen = (int) ($fila->con_examen ?? 0);

        return [
            'inscritos' => $inscritos,
            'con_examen' => $conExamen,
            'sin_examen' => $inscritos - $conExamen,
            'nombre_distinto' => (int) ($fila->nombre_distinto ?? 0),
        ];
    }

    /**
     * Ultimos archivos de la lectora que se subieron al proceso, aplicados o no.
     *
     * @return Collection<int, Importacion>
     */
    public function cargas(Proceso $proceso, int $cuantas = 5): Collection
    {
        return Importacion::query()
            ->with('usuario')
            ->where('id_pro', $proceso->id_pro)
            ->where('tipo_imp', TipoImportacion::Examenes)
            ->latest('id_imp')
            ->limit($cuantas)
            ->get();
    }

    /**
     * Carga anterior que ya aplico exactamente el mismo archivo (misma huella
     * sha256), para advertirlo en la vista previa.
     */
    public function cargaDelMismoArchivo(Proceso $proceso, string $archivo): ?Importacion
    {
        return Importacion::query()
            ->where('id_pro', $proceso->id_pro)
            ->where('tipo_imp', TipoImportacion::Examenes)
            ->where('aplicada_imp', true)
            ->where('hash_imp', (string) hash_file('sha256', $archivo))
            ->latest('id_imp')
            ->first();
    }

    /**
     * Borra todas las hojas del proceso, por ejemplo las de una prueba de la
     * lectora antes del examen real. Devuelve cuantas se borraron.
     */
    public function vaciar(Proceso $proceso): int
    {
        return Examen::query()->delProceso($proceso->id_pro)->delete();
    }
}
