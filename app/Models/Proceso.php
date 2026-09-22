<?php

namespace App\Models;

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
 * hora y lugar de la evaluacion tecnica.
 *
 * @property int $id_pro
 * @property string $codigo_pro
 * @property string $nombre_pro
 * @property string $entidad_pro
 * @property ?string $regimen_pro
 * @property ?Carbon $fecha_evaluacion_pro
 * @property ?string $hora_evaluacion_pro
 * @property ?string $lugar_evaluacion_pro
 * @property EstadoRegistro $estado_pro
 */
class Proceso extends Model
{
    /** @use HasFactory<ProcesoFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_proceso';

    protected $primaryKey = 'id_pro';

    protected $fillable = [
        'codigo_pro',
        'nombre_pro',
        'entidad_pro',
        'regimen_pro',
        'fecha_evaluacion_pro',
        'hora_evaluacion_pro',
        'lugar_evaluacion_pro',
        'estado_pro',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_evaluacion_pro' => 'date',
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
}
