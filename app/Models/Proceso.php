<?php

namespace App\Models;

use App\Enums\ComiteSeleccion;
use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\ProcesoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Proceso de seleccion de personal convocado por la Corte Superior. Reune los
 * datos de cabecera del Anexo 06-A: la entidad, el regimen laboral y la fecha,
 * hora y lugar de la evaluacion tecnica; y los del pie del Anexo 07, con el
 * que se publican los resultados.
 *
 * @property int $id_pro
 * @property string $codigo_pro
 * @property string $nombre_pro
 * @property string $entidad_pro
 * @property ?string $regimen_pro
 * @property ?Carbon $fecha_evaluacion_pro
 * @property ?string $hora_evaluacion_pro
 * @property ?string $lugar_evaluacion_pro
 * @property string $puntaje_minimo_pro
 * @property ?Carbon $fecha_limite_documentos_pro
 * @property ?string $correo_documentos_pro
 * @property ?Carbon $fecha_resultados_pro
 * @property string $ciudad_resultados_pro
 * @property ?ComiteSeleccion $comite_pro
 * @property EstadoRegistro $estado_pro
 */
class Proceso extends Model
{
    /** @use HasFactory<ProcesoFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_proceso';

    protected $primaryKey = 'id_pro';

    /**
     * Los mismos valores por defecto de la tabla, para que un proceso recien
     * creado los tenga sin volver a leerlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'puntaje_minimo_pro' => '3.90',
        'ciudad_resultados_pro' => 'Pucallpa',
    ];

    protected $fillable = [
        'codigo_pro',
        'nombre_pro',
        'entidad_pro',
        'regimen_pro',
        'fecha_evaluacion_pro',
        'hora_evaluacion_pro',
        'lugar_evaluacion_pro',
        'puntaje_minimo_pro',
        'fecha_limite_documentos_pro',
        'correo_documentos_pro',
        'fecha_resultados_pro',
        'ciudad_resultados_pro',
        'comite_pro',
        'estado_pro',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_evaluacion_pro' => 'date',
            'puntaje_minimo_pro' => 'decimal:2',
            'fecha_limite_documentos_pro' => 'date',
            'fecha_resultados_pro' => 'date',
            'comite_pro' => ComiteSeleccion::class,
            'estado_pro' => EstadoRegistro::class,
        ];
    }

    /**
     * @return HasMany<Puesto, $this>
     */
    public function puestos(): HasMany
    {
        return $this->hasMany(Puesto::class, 'id_pro', 'id_pro');
    }

    /**
     * @return HasMany<Inscripcion, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_pro', 'id_pro');
    }

    /**
     * @return HasMany<Importacion, $this>
     */
    public function importaciones(): HasMany
    {
        return $this->hasMany(Importacion::class, 'id_pro', 'id_pro');
    }

    /**
     * El pie del Anexo 07 no se puede publicar a medias: sin la fecha limite,
     * el correo, la fecha del documento y el comite quedaria incompleto.
     */
    public function publicacionCompleta(): bool
    {
        return $this->fecha_limite_documentos_pro !== null
            && filled($this->correo_documentos_pro)
            && $this->fecha_resultados_pro !== null
            && $this->comite_pro !== null;
    }

    /**
     * Linea de la firma: «Pucallpa, 03 de Setiembre del año 2026».
     */
    public function lugarYFechaDeResultados(): string
    {
        $fecha = $this->fecha_resultados_pro;

        return $fecha === null
            ? $this->ciudad_resultados_pro
            : "{$this->ciudad_resultados_pro}, {$fecha->format('d')} de ".ucfirst($fecha->translatedFormat('F'))." del año {$fecha->format('Y')}";
    }

    /**
     * Puntaje minimo como se escribe en el pie: «3,9».
     */
    public function puntajeMinimoTexto(): string
    {
        return rtrim(rtrim(number_format((float) $this->puntaje_minimo_pro, 2, ',', ''), '0'), ',');
    }
}
