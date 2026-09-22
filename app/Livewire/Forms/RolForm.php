<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Rol;
use Illuminate\Validation\Rule;
use Livewire\Form;

class RolForm extends Form
{
    public ?int $id = null;

    public string $nombre = '';

    public string $descripcion = '';

    /** @var list<string> */
    public array $permisos = [];

    public bool $esSuper = false;

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:60',
                Rule::unique('tbl_rol', 'nombre_rol')->ignore($this->id, 'id_rol'),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'permisos' => ['array'],
            'permisos.*' => [Rule::in(Permiso::valores())],
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
            'descripcion' => 'descripción',
            'permisos' => 'permisos',
            'estado' => 'estado',
        ];
    }

    public function llenar(Rol $rol): void
    {
        $this->id = $rol->id_rol;
        $this->nombre = $rol->nombre_rol;
        $this->descripcion = $rol->descripcion_rol ?? '';
        $this->permisos = array_column($rol->permisos(), 'value');
        $this->esSuper = $rol->esSuperAdministrador();
        $this->estado = $rol->estado_rol->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'nombre_rol' => trim($this->nombre),
            'descripcion_rol' => blank($this->descripcion) ? null : trim($this->descripcion),
            'permisos_rol' => $this->permisos,
            'estado_rol' => EstadoRegistro::from($this->estado),
        ];
    }
}
