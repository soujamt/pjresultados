<?php

namespace App\Models;

use Database\Factories\ExamenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hoja de respuestas de un postulante, calificada por la lectora optica. El
 * puntaje es la «Nota» del archivo, que en esta evaluacion es igual a los
 * aciertos: los errores, blancos y dobles no restan.
 *
 * Las respuestas se guardan como texto, una letra por pregunta: «-» es una
 * pregunta en blanco y «*» una marca doble.
 *
 * @property int $id_exa
 * @property int $id_pro
 * @property int $id_ins
 * @property ?int $id_imp
 * @property ?string $apellidos_nombres_exa
 * @property bool $nombre_coincide_exa
 * @property string $puntaje_exa
 * @property int $aciertos_exa
 * @property int $errores_exa
 * @property int $blancos_exa
 * @property int $dobles_exa
 * @property ?string $respuestas_exa
 * @property-read Inscripcion $inscripcion
 * @property-read ?Importacion $importacion
 */
class Examen extends Model
{
    /** @use HasFactory<ExamenFactory> */
    use HasFactory;

    public const BLANCO = '-';

    public const DOBLE = '*';

    protected $table = 'tbl_examen';

    protected $primaryKey = 'id_exa';

    protected $fillable = [
        'id_pro',
        'id_ins',
        'id_imp',
        'apellidos_nombres_exa',
        'nombre_coincide_exa',
        'puntaje_exa',
        'aciertos_exa',
        'errores_exa',
        'blancos_exa',
        'dobles_exa',
        'respuestas_exa',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nombre_coincide_exa' => 'boolean',
            'puntaje_exa' => 'decimal:3',
            'aciertos_exa' => 'integer',
            'errores_exa' => 'integer',
            'blancos_exa' => 'integer',
            'dobles_exa' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Inscripcion, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_ins', 'id_ins');
    }

    /**
     * @return BelongsTo<Importacion, $this>
     */
    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class, 'id_imp', 'id_imp');
    }

    /**
     * @param  Builder<$this>  $consulta
     */
    public function scopeDelProceso(Builder $consulta, int $idProceso): void
    {
        $consulta->where('id_pro', $idProceso);
    }

    /**
     * Respuestas marcadas, numeradas desde la pregunta 1.
     *
     * @return array<int, string>
     */
    public function respuestas(): array
    {
        if ($this->respuestas_exa === null || $this->respuestas_exa === '') {
            return [];
        }

        $respuestas = mb_str_split($this->respuestas_exa);

        return array_combine(range(1, count($respuestas)), $respuestas);
    }

    public function totalDePreguntas(): int
    {
        return $this->aciertos_exa + $this->errores_exa + $this->blancos_exa + $this->dobles_exa;
    }

    /**
     * Puntaje sin los ceros decimales que agrega la lectora: 30,000 pasa a 30.
     */
    public function puntaje(): string
    {
        return rtrim(rtrim(number_format((float) $this->puntaje_exa, 3, '.', ''), '0'), '.');
    }
}
