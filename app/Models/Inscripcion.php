<?php

namespace App\Models;

use Database\Factories\InscripcionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Postulante inscrito a un puesto del proceso. El documento de identidad es
 * la llave con la que se cruzaran las hojas del examen, por eso no se repite
 * dentro de un mismo proceso.
 *
 * @property int $id_ins
 * @property int $id_pro
 * @property int $id_pue
 * @property ?int $numero_ins
 * @property string $documento_ins
 * @property string $apellidos_nombres_ins
 * @property-read Proceso $proceso
 * @property-read Puesto $puesto
 * @property-read ?Examen $examen
 */
class Inscripcion extends Model
{
    /** @use HasFactory<InscripcionFactory> */
    use HasFactory;

    protected $table = 'tbl_inscripcion';

    protected $primaryKey = 'id_ins';

    protected $fillable = [
        'id_pro',
        'id_pue',
        'numero_ins',
        'documento_ins',
        'apellidos_nombres_ins',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero_ins' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Proceso, $this>
     */
    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_pro', 'id_pro');
    }

    /**
     * @return BelongsTo<Puesto, $this>
     */
    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class, 'id_pue', 'id_pue')->withTrashed();
    }

    /**
     * @return HasOne<Examen, $this>
     */
    public function examen(): HasOne
    {
        return $this->hasOne(Examen::class, 'id_ins', 'id_ins');
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeDelProceso(Builder $consulta, int $idProceso): void
    {
        $consulta->where('id_pro', $idProceso);
    }
}
