<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Proceso;
use Illuminate\Database\Seeder;

class ProcesoSeeder extends Seeder
{
    /**
     * Proceso del Anexo 06-A publicado por la Corte Superior de Justicia de
     * Ucayali. Los puestos se cargan desde ese mismo archivo con el comando
     * pj:importar-puestos o desde la pantalla de puestos.
     */
    public function run(): void
    {
        Proceso::firstOrCreate(
            ['codigo_pro' => '002-2026-UE-UCAYALI'],
            [
                'nombre_pro' => 'Proceso de Selección de Personal Indeterminado N° 002-2026-UE-UCAYALI',
                'entidad_pro' => 'Corte Superior de Justicia de Ucayali',
                'regimen_pro' => 'Decreto Legislativo N° 728, a plazo indeterminado',
                'fecha_evaluacion_pro' => '2026-09-26',
                'hora_evaluacion_pro' => '8:00 am. a 9:30 am.',
                'lugar_evaluacion_pro' => 'Universidad Nacional Intercultural de la Amazonía, San José Km. 0.5, Pucallpa',
                'estado_pro' => EstadoRegistro::Habilitado,
            ],
        );
    }
}
