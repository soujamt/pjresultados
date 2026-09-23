<?php

namespace App\Services\Evaluacion;

use App\Enums\CondicionResultado;
use App\Models\Descalificacion;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Resultados de la evaluacion tecnica con las reglas del Anexo 07:
 *
 *   nota obtenida  = aciertos de la hoja (sobre 30)
 *   nota parcial   = nota obtenida × 20 / 30
 *   puntaje        = nota parcial × 0,3
 *   condicion      = APTO si el puntaje alcanza el minimo del proceso (3,9)
 *
 * Con el minimo de 3,9 hacen falta 20 aciertos: 20 dan 4,00 y 19 dan 3,80.
 * El puntaje se calcula con la nota parcial exacta y se redondea a dos
 * decimales al final; la nota parcial redondeada (17,33) es solo para leer.
 *
 * Quien no tiene hoja no se presento, y quien fue descalificado por el comite
 * queda NO APTO con puntaje 0 aunque tenga hoja. Los resultados se calculan
 * al momento con lo cargado, asi que siempre coinciden con los examenes.
 */
class ResultadoService
{
    public const NOTA_MAXIMA = 30;

    public const ESCALA = 20;

    public const PESO = 0.3;

    public const NO_ALCANZO = 'DESCALIFICADO/A - NO ALCANZÓ EL PUNTAJE MÍNIMO APROBATORIO';

    public const NO_SE_PRESENTO = 'DESCALIFICADO/A - NO SE PRESENTÓ';

    /**
     * Resultados por puesto, en el orden del codigo de puesto. Los puestos
     * sin inscritos no se incluyen: no hay nada que publicar.
     *
     * @return list<ResultadoDePuesto>
     */
    public function porPuesto(Proceso $proceso, ?int $unidad = null, ?int $puesto = null): array
    {
        $puestos = Puesto::query()
            ->with('unidad')
            ->delProceso($proceso->id_pro)
            ->when($unidad !== null, fn (Builder $consulta) => $consulta->deLaUnidad($unidad))
            ->when($puesto !== null, fn (Builder $consulta) => $consulta->whereKey($puesto))
            ->orderBy('codigo_pue')
            ->get();

        $inscripciones = Inscripcion::query()
            ->with([
                'examen:id_exa,id_ins,aciertos_exa',
                'descalificacion:id_des,id_ins,motivo_des',
            ])
            ->delProceso($proceso->id_pro)
            ->whereIn('id_pue', $puestos->modelKeys())
            ->get(['id_ins', 'id_pro', 'id_pue', 'documento_ins', 'apellidos_nombres_ins'])
            ->groupBy('id_pue');

        $minimo = (int) round((float) $proceso->puntaje_minimo_pro * 100);
        $resultados = [];

        foreach ($puestos as $delPuesto) {
            $inscritos = $inscripciones->get($delPuesto->id_pue);

            if ($inscritos !== null && $inscritos->isNotEmpty()) {
                $resultados[] = new ResultadoDePuesto($delPuesto, $this->ordenDeMerito($inscritos->all(), $minimo));
            }
        }

        return $resultados;
    }

    /**
     * Descalifica al postulante con el motivo que se publicara. Si ya estaba
     * descalificado, se reemplaza el motivo.
     */
    public function descalificar(Inscripcion $inscripcion, string $motivo, ?Usuario $usuario = null): Descalificacion
    {
        return Descalificacion::updateOrCreate(
            ['id_ins' => $inscripcion->id_ins],
            [
                'id_pro' => $inscripcion->id_pro,
                'motivo_des' => mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $motivo))),
                'id_usu' => $usuario?->id_usu,
            ],
        );
    }

    public function quitarDescalificacion(Inscripcion $inscripcion): void
    {
        $inscripcion->descalificacion()->delete();
    }

    /**
     * @param  array{puntaje_minimo_pro: string, fecha_limite_documentos_pro: string, correo_documentos_pro: string, fecha_resultados_pro: string, ciudad_resultados_pro: string, comite_pro: string}  $datos
     */
    public function guardarPublicacion(Proceso $proceso, array $datos): void
    {
        $proceso->update($datos);
    }

    /**
     * Nombre del archivo que se descarga, sin extension.
     */
    public function nombreArchivo(Proceso $proceso, ?Puesto $puesto = null): string
    {
        return Str::slug(implode(' ', array_filter([
            'resultados evaluacion tecnica',
            $proceso->codigo_pro,
            $puesto?->codigo_pue,
        ])));
    }

    /**
     * @param  array<int, Inscripcion>  $inscritos
     * @return list<FilaDeResultado>
     */
    private function ordenDeMerito(array $inscritos, int $minimo): array
    {
        $calificados = array_map(fn (Inscripcion $inscripcion): array => $this->calificar($inscripcion, $minimo), $inscritos);

        /* De mayor a menor puntaje; los empates, por orden alfabetico. */
        usort($calificados, fn (array $a, array $b): int => [$b['puntaje'], $a['clave']] <=> [$a['puntaje'], $b['clave']]);

        $filas = [];

        foreach ($calificados as $indice => $calificado) {
            $filas[] = new FilaDeResultado(
                numero: $indice + 1,
                inscripcion: $calificado['inscripcion'],
                nota: $calificado['nota'],
                notaParcial: $calificado['notaParcial'],
                puntaje: $calificado['puntaje'],
                condicion: $calificado['condicion'],
                observacion: $calificado['observacion'],
                rindio: $calificado['rindio'],
                descalificado: $calificado['descalificado'],
            );
        }

        return $filas;
    }

    /**
     * @param  int  $minimo  puntaje minimo en centesimos (3,9 = 390)
     * @return array{inscripcion: Inscripcion, clave: string, nota: int, notaParcial: float, puntaje: float, condicion: CondicionResultado, observacion: ?string, rindio: bool, descalificado: bool}
     */
    private function calificar(Inscripcion $inscripcion, int $minimo): array
    {
        $calificacion = [
            'inscripcion' => $inscripcion,
            'clave' => self::claveAlfabetica($inscripcion->apellidos_nombres_ins),
            'nota' => 0,
            'notaParcial' => 0.0,
            'puntaje' => 0.0,
            'condicion' => CondicionResultado::NoApto,
            'rindio' => $inscripcion->examen !== null,
            'descalificado' => $inscripcion->descalificacion !== null,
        ];

        if ($inscripcion->descalificacion !== null) {
            return $calificacion + ['observacion' => $inscripcion->descalificacion->motivo_des];
        }

        if ($inscripcion->examen === null) {
            return $calificacion + ['observacion' => self::NO_SE_PRESENTO];
        }

        $nota = $inscripcion->examen->aciertos_exa;
        $notaParcial = $nota * self::ESCALA / self::NOTA_MAXIMA;
        $puntaje = round($notaParcial * self::PESO, 2);
        $apto = (int) round($puntaje * 100) >= $minimo;

        return [
            'nota' => $nota,
            'notaParcial' => $notaParcial,
            'puntaje' => $puntaje,
            'condicion' => $apto ? CondicionResultado::Apto : CondicionResultado::NoApto,
            'observacion' => $apto ? null : self::NO_ALCANZO,
        ] + $calificacion;
    }

    /**
     * Orden alfabetico en castellano sin depender de la extension intl: la Ñ
     * va despues de la N y las tildes no cuentan.
     */
    private static function claveAlfabetica(string $nombres): string
    {
        return Str::ascii(strtr(mb_strtoupper($nombres), ['Ñ' => 'N~']));
    }
}
