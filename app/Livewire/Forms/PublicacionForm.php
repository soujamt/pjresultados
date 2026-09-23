<?php

namespace App\Livewire\Forms;

use App\Enums\ComiteSeleccion;
use App\Models\Proceso;
use App\Services\Evaluacion\ResultadoService;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Datos del pie del Anexo 07: el puntaje minimo para ser apto, hasta cuando y
 * a que correo envian sus documentos los aptos, y quien y cuando publica.
 */
class PublicacionForm extends Form
{
    public string $puntajeMinimo = '3.9';

    public ?string $fechaLimite = null;

    public string $correo = '';

    public ?string $fechaResultados = null;

    public string $ciudad = 'Pucallpa';

    public string $comite = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /* Con las 30 preguntas correctas: 20 × 0,3 = 6. */
        $puntajeMaximo = ResultadoService::ESCALA * ResultadoService::PESO;

        return [
            'puntajeMinimo' => ['required', 'numeric', 'decimal:0,2', 'gt:0', "lte:{$puntajeMaximo}"],
            'fechaLimite' => ['required', 'date'],
            'correo' => ['required', 'email', 'max:150'],
            'fechaResultados' => ['required', 'date'],
            'ciudad' => ['required', 'string', 'max:60'],
            'comite' => ['required', Rule::enum(ComiteSeleccion::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'puntajeMinimo' => 'puntaje mínimo',
            'fechaLimite' => 'fecha límite',
            'correo' => 'correo',
            'fechaResultados' => 'fecha de publicación',
            'ciudad' => 'ciudad',
            'comite' => 'comité',
        ];
    }

    public function llenar(Proceso $proceso): void
    {
        $this->puntajeMinimo = rtrim(rtrim($proceso->puntaje_minimo_pro, '0'), '.');
        $this->fechaLimite = $proceso->fecha_limite_documentos_pro?->toDateString();
        $this->correo = $proceso->correo_documentos_pro ?? '';
        $this->fechaResultados = $proceso->fecha_resultados_pro?->toDateString();
        $this->ciudad = $proceso->ciudad_resultados_pro;
        $this->comite = $proceso->comite_pro->value ?? '';
    }

    /**
     * @return array{puntaje_minimo_pro: string, fecha_limite_documentos_pro: string, correo_documentos_pro: string, fecha_resultados_pro: string, ciudad_resultados_pro: string, comite_pro: string}
     */
    public function datos(): array
    {
        return [
            'puntaje_minimo_pro' => $this->puntajeMinimo,
            'fecha_limite_documentos_pro' => (string) $this->fechaLimite,
            'correo_documentos_pro' => mb_strtolower(trim($this->correo)),
            'fecha_resultados_pro' => (string) $this->fechaResultados,
            'ciudad_resultados_pro' => trim($this->ciudad),
            'comite_pro' => $this->comite,
        ];
    }
}
