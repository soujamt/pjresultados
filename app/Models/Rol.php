<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Concerns\TieneEstado;
use Database\Factories\RolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Rol de acceso. Los permisos concedidos se guardan como una lista de valores
 * del enum Permiso en una sola columna: son pocos y siempre se leen juntos,
 * asi que una tabla pivote solo agregaria un join a cada request.
 *
 * El rol marcado como super administrador tiene acceso a todo sin importar
 * lo que diga su lista, incluidos los permisos que se agreguen despues.
 *
 * @property int $id_rol
 * @property string $nombre_rol
 * @property ?string $descripcion_rol
 * @property ?list<string> $permisos_rol
 * @property bool $es_super_rol
 * @property EstadoRegistro $estado_rol
 */
class Rol extends Model
{
    /** @use HasFactory<RolFactory> */
    use HasFactory, SoftDeletes, TieneEstado;

    protected $table = 'tbl_rol';

    protected $primaryKey = 'id_rol';

    protected $fillable = [
        'nombre_rol',
        'descripcion_rol',
        'permisos_rol',
        'es_super_rol',
        'estado_rol',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permisos_rol' => 'array',
            'es_super_rol' => 'boolean',
            'estado_rol' => EstadoRegistro::class,
        ];
    }

    /**
     * @return HasMany<Usuario, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'id_rol');
    }

    public function esSuperAdministrador(): bool
    {
        return $this->es_super_rol;
    }

    public function tiene(Permiso $permiso): bool
    {
        return $this->es_super_rol || in_array($permiso->value, $this->permisos_rol ?? [], true);
    }

    /**
     * Permisos del rol ya resueltos a casos del enum. Se descartan los valores
     * que quedaron huerfanos al renombrar o retirar un permiso del codigo.
     *
     * @return list<Permiso>
     */
    public function permisos(): array
    {
        if ($this->es_super_rol) {
            return Permiso::cases();
        }

        return array_values(array_filter(
            array_map(fn (string $valor) => Permiso::tryFrom($valor), $this->permisos_rol ?? [])
        ));
    }
}
