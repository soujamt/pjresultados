<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Puesto;
use App\Services\Seleccion\AnexoPostulantes;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PuestoForm extends Form
{
    public ?int $id = null;

    public ?int $proceso = null;

    public string $codigo = '';

    public string $nombre = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proceso' => ['required', 'integer', Rule::exists('tbl_proceso', 'id_pro')->whereNull('deleted_at')],
            'codigo' => [
                'required', 'string', 'max:20', 'regex:/^[0-9A-Za-z_\-]+$/',
                Rule::unique('tbl_puesto', 'codigo_pue')
                    ->where('id_pro', $this->proceso)
                    ->ignore($this->id, 'id_pue')
                    ->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'proceso' => 'proceso',
            'codigo' => 'código',
            'nombre' => 'nombre del puesto',
            'estado' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'El proceso ya tiene un puesto con ese código.',
            'codigo.regex' => 'El código solo admite números, letras y guiones (por ejemplo 00340-1).',
        ];
    }

    /**
     * El codigo se normaliza antes de validar para que «00306_1» choque con
     * el «00306-1» que ya existe.
     */
    public function normalizar(): void
    {
        $this->codigo = AnexoPostulantes::normalizarCodigo($this->codigo);
    }

    public function llenar(Puesto $puesto): void
    {
        $this->id = $puesto->id_pue;
        $this->proceso = $puesto->id_pro;
        $this->codigo = $puesto->codigo_pue;
        $this->nombre = $puesto->nombre_pue;
        $this->estado = $puesto->estado_pue->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'id_pro' => (int) $this->proceso,
            'codigo_pue' => $this->codigo,
            'nombre_pue' => mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $this->nombre))),
            'estado_pue' => EstadoRegistro::from($this->estado),
        ];
    }
}
