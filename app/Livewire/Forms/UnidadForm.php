<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Unidad;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UnidadForm extends Form
{
    public ?int $id = null;

    public string $nombre = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * El nombre se normaliza antes de validar, para que la regla de unicidad
     * compare lo mismo que se va a guardar.
     */
    public function normalizar(): void
    {
        $this->nombre = Unidad::normalizarNombre($this->nombre);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('tbl_unidad', 'nombre_uni')->ignore($this->id, 'id_uni')->whereNull('deleted_at'),
            ],
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
            'estado' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una unidad de organización con ese nombre.',
        ];
    }

    public function llenar(Unidad $unidad): void
    {
        $this->id = $unidad->id_uni;
        $this->nombre = $unidad->nombre_uni;
        $this->estado = $unidad->estado_uni->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'nombre_uni' => $this->nombre,
            'estado_uni' => EstadoRegistro::from($this->estado),
        ];
    }
}
