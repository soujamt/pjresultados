<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\UnidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Unidad de organizacion de la Corte Superior: el juzgado, la sala o el modulo
 * al que pertenece un puesto convocado («MÓDULO PENAL CENTRAL», «SALA CIVIL -
 * CALLERIA»). Sirve para identificar y agrupar a los postulantes.
 *
 * @property int $id_uni
 * @property string $nombre_uni
 * @property EstadoRegistro $estado_uni
 */
class Unidad extends Model
{
    /** @use HasFactory<UnidadFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_unidad';

    protected $primaryKey = 'id_uni';

    protected $fillable = [
        'nombre_uni',
        'estado_uni',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado_uni' => EstadoRegistro::class,
        ];
    }

    /**
     * @return HasMany<Puesto, $this>
     */
    public function puestos(): HasMany
    {
        return $this->hasMany(Puesto::class, 'id_uni', 'id_uni');
    }

    /**
     * @return HasManyThrough<Inscripcion, Puesto, $this>
     */
    public function inscripciones(): HasManyThrough
    {
        return $this->hasManyThrough(Inscripcion::class, Puesto::class, 'id_uni', 'id_pue', 'id_uni', 'id_pue');
    }

    /**
     * Nombre tal como se guarda: en mayusculas y sin espacios de sobra.
     */
    public static function normalizarNombre(string $nombre): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $nombre)));
    }

    /**
     * Clave para reconocer la misma unidad escrita con o sin tildes: en los
     * anexos aparece tanto «MÓDULO» como «MODULO».
     */
    public static function claveDeNombre(string $nombre): string
    {
        return Str::upper(Str::ascii(self::normalizarNombre($nombre)));
    }
}
