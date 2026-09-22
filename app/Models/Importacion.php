<?php

namespace App\Models;

use App\Enums\TipoImportacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro de auditoria de cada archivo cargado: quien lo subio, su huella
 * sha256, cuantas filas traia y los errores que se encontraron. Tambien se
 * guardan las cargas rechazadas, con `aplicada_imp` en falso.
 *
 * @property int $id_imp
 * @property int $id_pro
 * @property ?int $id_usu
 * @property TipoImportacion $tipo_imp
 * @property string $archivo_imp
 * @property string $hash_imp
 * @property int $filas_imp
 * @property int $creados_imp
 * @property int $actualizados_imp
 * @property ?list<string> $errores_imp
 * @property bool $aplicada_imp
 * @property Carbon $created_at
 * @property-read ?Usuario $usuario
 */
class Importacion extends Model
{
    protected $table = 'tbl_importacion';

    protected $primaryKey = 'id_imp';

    protected $fillable = [
        'id_pro',
        'id_usu',
        'tipo_imp',
        'archivo_imp',
        'hash_imp',
        'filas_imp',
        'creados_imp',
        'actualizados_imp',
        'errores_imp',
        'aplicada_imp',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_imp' => TipoImportacion::class,
            'errores_imp' => 'array',
            'aplicada_imp' => 'boolean',
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
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usu', 'id_usu')->withTrashed();
    }
}
