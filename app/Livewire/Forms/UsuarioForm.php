<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Usuario;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Form;

class UsuarioForm extends Form
{
    public ?int $id = null;

    public string $nombre = '';

    public string $usuario = '';

    public ?int $rol = null;

    public string $clave = '';

    public string $clave_confirmation = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'usuario' => [
                'required', 'email', 'max:150',
                Rule::unique('tbl_usuario', 'usuario_usu')->ignore($this->id, 'id_usu'),
            ],
            'rol' => ['required', 'integer', Rule::exists('tbl_rol', 'id_rol')->whereNull('deleted_at')],
            'clave' => [$this->id === null ? 'required' : 'nullable', 'string', Password::min(8), 'confirmed'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'usuario' => 'correo',
            'rol' => 'rol',
            'clave' => 'contraseña',
            'estado' => 'estado',
        ];
    }

    public function llenar(Usuario $usuario): void
    {
        $this->id = $usuario->id_usu;
        $this->nombre = $usuario->nombre_usu;
        $this->usuario = $usuario->usuario_usu;
        $this->rol = $usuario->id_rol;
        $this->clave = '';
        $this->clave_confirmation = '';
        $this->estado = $usuario->estado_usu->value;
    }

    /**
     * @return array{id_rol: int, nombre_usu: string, usuario_usu: string, estado_usu: EstadoRegistro, clave_usu: ?string}
     */
    public function datos(): array
    {
        return [
            'id_rol' => (int) $this->rol,
            'nombre_usu' => trim($this->nombre),
            'usuario_usu' => (string) Str::of($this->usuario)->trim()->lower(),
            'estado_usu' => EstadoRegistro::from($this->estado),
            'clave_usu' => $this->clave === '' ? null : $this->clave,
        ];
    }
}
