<?php

namespace App\Console\Commands;

use App\Services\Evaluacion\ImportadorExamenes;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('pj:importar-examenes {archivo : Ruta del .txt que exporta la lectora óptica} {--proceso= : Código del proceso; por defecto el habilitado más reciente}')]
#[Description('Carga las hojas calificadas por la lectora óptica y las cruza por DNI con los inscritos')]
class ImportarExamenes extends Command
{
    use ResuelveProceso;

    public function handle(ImportadorExamenes $importador): int
    {
        $proceso = $this->resolverProceso($this->option('proceso'));

        if ($proceso === null) {
            return self::FAILURE;
        }

        try {
            $resultado = $importador->importar($proceso, (string) $this->argument('archivo'));
        } catch (RuntimeException $error) {
            $this->components->error($error->getMessage());

            return self::FAILURE;
        }

        foreach ($resultado->errores as $error) {
            $this->components->warn($error);
        }

        $resultado->aplicada
            ? $this->components->info("{$proceso->codigo_pro}: ".$resultado->mensaje('examen(es)'))
            : $this->components->error($resultado->mensaje('examen(es)'));

        return $resultado->aplicada ? self::SUCCESS : self::FAILURE;
    }
}
