<?php

namespace App\Models;

use App\Enums\EstadoRegistro;
use App\Models\Concerns\TieneEstado;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cuenta de acceso al sistema. Se inicia sesion con el correo guardado en
 * `usuario_usu`.
 *
 * @property int $id_usu
 * @property int $id_rol
 * @property string $nombre_usu
 * @property string $usuario_usu
 * @property string $clave_usu
 * @property EstadoRegistro $estado_usu
 * @property-read Rol $rol
 */
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable, SoftDeletes, TieneEstado;

    protected $table = 'tbl_usuario';

    protected $primaryKey = 'id_usu';

    protected $fillable = [
        'id_rol',
        'nombre_usu',
        'usuario_usu',
        'clave_usu',
        'estado_usu',
    ];

    protected $hidden = [
        'clave_usu',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clave_usu' => 'hashed',
            'estado_usu' => EstadoRegistro::class,
        ];
    }

    /**
     * La columna de la contrasena no se llama `password`, asi que hay que
     * decirselo al guard.
     */
    public function getAuthPassword(): string
    {
        return $this->clave_usu;
    }

    public function getAuthPasswordName(): string
    {
        return 'clave_usu';
    }

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /**
     * Iniciales para el avatar de la cabecera.
     */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/', trim($this->nombre_usu)) ?: [];

        return mb_strtoupper(mb_substr($palabras[0] ?? '', 0, 1).mb_substr($palabras[1] ?? '', 0, 1));
    }
}
