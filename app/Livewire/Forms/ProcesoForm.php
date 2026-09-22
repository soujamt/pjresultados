<?php

namespace App\Livewire\Forms;

use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProcesoForm extends Form
{
    public ?int $id = null;

    public string $codigo = '';

    public string $nombre = '';

    public string $entidad = 'Corte Superior de Justicia de Ucayali';

    public string $regimen = '';

    public ?string $fechaEvaluacion = null;

    public string $horaEvaluacion = '';

    public string $lugarEvaluacion = '';

    public int $estado = EstadoRegistro::Habilitado->value;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:60',
                Rule::unique('tbl_proceso', 'codigo_pro')->ignore($this->id, 'id_pro')->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'entidad' => ['required', 'string', 'max:150'],
            'regimen' => ['nullable', 'string', 'max:150'],
            'fechaEvaluacion' => ['nullable', 'date'],
            'horaEvaluacion' => ['nullable', 'string', 'max:60'],
            'lugarEvaluacion' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::enum(EstadoRegistro::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'codigo' => 'código',
            'nombre' => 'nombre',
            'entidad' => 'entidad',
            'regimen' => 'régimen laboral',
            'fechaEvaluacion' => 'fecha de la evaluación',
            'horaEvaluacion' => 'hora de la evaluación',
            'lugarEvaluacion' => 'lugar de la evaluación',
            'estado' => 'estado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Ya existe un proceso con ese código.',
        ];
    }

    public function llenar(Proceso $proceso): void
    {
        $this->id = $proceso->id_pro;
        $this->codigo = $proceso->codigo_pro;
        $this->nombre = $proceso->nombre_pro;
        $this->entidad = $proceso->entidad_pro;
        $this->regimen = $proceso->regimen_pro ?? '';
        $this->fechaEvaluacion = $proceso->fecha_evaluacion_pro?->toDateString();
        $this->horaEvaluacion = $proceso->hora_evaluacion_pro ?? '';
        $this->lugarEvaluacion = $proceso->lugar_evaluacion_pro ?? '';
        $this->estado = $proceso->estado_pro->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            'codigo_pro' => mb_strtoupper(trim($this->codigo)),
            'nombre_pro' => trim($this->nombre),
            'entidad_pro' => trim($this->entidad),
            'regimen_pro' => blank($this->regimen) ? null : trim($this->regimen),
            'fecha_evaluacion_pro' => $this->fechaEvaluacion ?: null,
            'hora_evaluacion_pro' => blank($this->horaEvaluacion) ? null : trim($this->horaEvaluacion),
            'lugar_evaluacion_pro' => blank($this->lugarEvaluacion) ? null : trim($this->lugarEvaluacion),
            'estado_pro' => EstadoRegistro::from($this->estado),
        ];
    }
}
