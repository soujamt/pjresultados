<?php

namespace App\Console\Commands;

use App\Services\Seleccion\ImportadorPuestos;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('pj:importar-puestos {archivo : Ruta del Excel con el formato del Anexo 06-A} {--proceso= : Código del proceso; por defecto el habilitado más reciente}')]
#[Description('Carga los puestos y sus códigos desde el listado de postulantes del Anexo 06-A')]
class ImportarPuestos extends Command
{
    use ResuelveProceso;

    public function handle(ImportadorPuestos $importador): int
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
            ? $this->components->info("{$proceso->codigo_pro}: ".$resultado->mensaje('puesto(s)'))
            : $this->components->error($resultado->mensaje('puesto(s)'));

        if ($resultado->aplicada) {
            $this->table(
                ['Código', 'Puesto'],
                $proceso->puestos()->orderBy('codigo_pue')->get(['codigo_pue', 'nombre_pue'])->toArray(),
            );
        }

        return $resultado->aplicada ? self::SUCCESS : self::FAILURE;
    }
}
