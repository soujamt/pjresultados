<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\PuestoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Puesto convocado dentro de un proceso. El codigo es el del cuadro de puestos
 * del Poder Judicial y puede llevar sufijo cuando un mismo cargo se convoca
 * para varias dependencias: `00340-1`, `00340-2`.
 *
 * @property int $id_pue
 * @property int $id_pro
 * @property string $codigo_pue
 * @property string $nombre_pue
 * @property EstadoRegistro $estado_pue
 * @property-read Proceso $proceso
 */
class Puesto extends Model
{
    /** @use HasFactory<PuestoFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_puesto';

    protected $primaryKey = 'id_pue';

    protected $fillable = [
        'id_pro',
        'codigo_pue',
        'nombre_pue',
        'estado_pue',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_pue' => EstadoRegistro::class,
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
     * @return HasMany<Inscripcion, $this>
     */
    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'id_pue', 'id_pue');
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeDelProceso(Builder $consulta, int $idProceso): void
    {
        $consulta->where('id_pro', $idProceso);
    }

    /**
     * Como aparece en el Anexo: «ASISTENTE JUDICIAL (00340-1)».
     */
    public function denominacion(): string
    {
        return "{$this->nombre_pue} ({$this->codigo_pue})";
    }
}
