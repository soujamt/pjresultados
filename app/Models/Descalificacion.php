<?php

namespace App\Models;

use Database\Factories\DescalificacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Descalificacion registrada por el comite por un hecho que la lectora no ve,
 * como retirarse de la sala. Tenga o no hoja de examen, el postulante queda
 * NO APTO con puntaje 0 y el motivo se publica como observacion.
 *
 * @property int $id_des
 * @property int $id_pro
 * @property int $id_ins
 * @property string $motivo_des
 * @property ?int $id_usu
 * @property Carbon $created_at
 * @property-read Inscripcion $inscripcion
 * @property-read ?Usuario $usuario
 */
class Descalificacion extends Model
{
    /** @use HasFactory<DescalificacionFactory> */
    use HasFactory;

    /**
     * Motivos del formato del Anexo 07 y de publicaciones anteriores. El
     * comite tambien puede escribir otro.
     */
    public const MOTIVOS = [
        'DESCALIFICADO/A - SE RETIRÓ DE LA SALA DURANTE LA EVALUACIÓN TÉCNICA CONFORME SE DEJÓ CONSTANCIA EN AUDIO Y VIDEO',
        'DESCALIFICADO/A - INCURRIÓ EN FALTA EN EL MOMENTO DE LA EVALUACIÓN',
        'DESCALIFICADO/A - NO ENVIÓ SU EVALUACIÓN TÉCNICA',
        'DESCALIFICADO/A - FALLA DE CONEXIÓN',
    ];

    protected $table = 'tbl_descalificacion';

    protected $primaryKey = 'id_des';

    protected $fillable = [
        'id_pro',
        'id_ins',
        'motivo_des',
        'id_usu',
    ];

    /**
     * @return BelongsTo<Inscripcion, $this>
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_ins', 'id_ins');
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usu', 'id_usu')->withTrashed();
    }
}
