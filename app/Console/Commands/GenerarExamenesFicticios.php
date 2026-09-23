<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Services\Evaluacion\GeneradorDeExamenesFicticios;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

#[Signature('pj:generar-examenes-ficticios
    {--faltantes=0 : Cuántos inscritos no se presentaron; se eligen al azar}
    {--proceso= : Código del proceso; por defecto el habilitado más reciente}
    {--salida= : Ruta del .txt; por defecto storage/app/private/lectora/}
    {--semilla= : Número entero para obtener siempre el mismo archivo}')]
#[Description('Genera un .txt de prueba con el formato de la lectora óptica y calificaciones ficticias (30 preguntas)')]
class GenerarExamenesFicticios extends Command
{
    use ResuelveProceso;

    public function handle(GeneradorDeExamenesFicticios $generador): int
    {
        if ($this->laravel->isProduction()) {
            $this->components->error('Este comando inventa calificaciones: no se ejecuta en producción.');

            return self::FAILURE;
        }

        $faltantes = (string) $this->option('faltantes');
        $semilla = $this->option('semilla');

        if (! ctype_digit($faltantes) || ($semilla !== null && ! ctype_digit((string) $semilla))) {
            $this->components->error('--faltantes y --semilla tienen que ser números enteros sin signo.');

            return self::FAILURE;
        }

        $proceso = $this->resolverProceso($this->option('proceso'));

        if ($proceso === null) {
            return self::FAILURE;
        }

        try {
            $generado = $generador->generar($proceso, (int) $faltantes, $semilla === null ? null : (int) $semilla);
        } catch (RuntimeException $error) {
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }

        $ruta = $this->option('salida')
            ?: storage_path('app/private/lectora/examenes-ficticios-'.$proceso->codigo_pro.'-'.now()->format('Ymd-His').'.txt');

        File::ensureDirectoryExists(dirname($ruta));
        File::put($ruta, $generado['contenido']);

        $puntajes = collect($generado['puntajes']);
        $this->components->info("{$proceso->codigo_pro}: {$puntajes->count()} hoja(s) de {$generado['inscritos']} inscritos; {$faltantes} no se presentaron.");
        $this->components->twoColumnDetail('Archivo', $ruta);
        $this->components->twoColumnDetail(
            'Puntaje (sobre '.GeneradorDeExamenesFicticios::PREGUNTAS.')',
            'máximo '.$puntajes->max().' · promedio '.number_format((float) $puntajes->avg(), 1, ',', '').' · mínimo '.$puntajes->min(),
        );

        if ($generado['faltantes'] !== []) {
            $this->newLine();
            $this->line('  Quienes no se presentaron (deben aparecer en la vista previa como «Quedarán sin examen»):');
            $this->table(
                ['DNI', 'Apellidos y nombres'],
                array_map(fn (Inscripcion $inscripcion): array => [$inscripcion->documento_ins, $inscripcion->apellidos_nombres_ins], $generado['faltantes']),
            );
        }

        $this->components->warn('Las calificaciones son inventadas: úsalo solo para ensayar la carga y los resultados.');

        return self::SUCCESS;
    }
}
