<?php

namespace App\Console\Commands;

use App\Services\Seleccion\ImportadorInscripciones;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('pj:importar-inscripciones {archivo : Ruta del Excel del Anexo 06-A con la columna DNI} {--proceso= : Código del proceso; por defecto el habilitado más reciente}')]
#[Description('Carga a los postulantes inscritos desde el Anexo 06-A (requiere la columna DNI)')]
class ImportarInscripciones extends Command
{
    use ResuelveProceso;

    public function handle(ImportadorInscripciones $importador): int
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
            ? $this->components->info("{$proceso->codigo_pro}: ".$resultado->mensaje('inscrito(s)'))
            : $this->components->error($resultado->mensaje('inscrito(s)'));

        return $resultado->aplicada ? self::SUCCESS : self::FAILURE;
    }
}
