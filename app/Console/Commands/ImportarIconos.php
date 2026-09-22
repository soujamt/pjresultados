<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Trae íconos de Hugeicons Free (licencia MIT) y los deja como íconos de Flux.
 *
 * Flux busca primero en resources/views/flux/icon, asi que un archivo ahi se
 * usa igual que cualquier Heroicon: <flux:icon.home-01 /> o icon="home-01".
 * El paquete de npm pesa 80 MB para cuatro mil iconos; aqui se descargan
 * solo los que el sistema usa, fijados a una version para que no cambien
 * solos. Es el mismo mecanismo de `php artisan flux:icon` para Lucide.
 */
#[Signature('pj:iconos
    {nombres?* : Íconos en kebab-case (home-01), por su nombre en Hugeicons (Home01Icon) o con alias archivo=origen (eye=view)}
    {--actualizar : Vuelve a descargar todos los íconos ya generados}
    {--forzar : Permite reemplazar un Heroicon del mismo nombre}
    {--destino= : Carpeta de salida (por defecto resources/views/flux/icon)}')]
#[Description('Descarga íconos de Hugeicons Free y los convierte en íconos de Flux')]
class ImportarIconos extends Command
{
    /**
     * Version fija del paquete @hugeicons/core-free-icons.
     */
    public const VERSION = '4.3.5';

    private const URL = 'https://unpkg.com/@hugeicons/core-free-icons@%s/dist/esm/%s.js';

    public function handle(Filesystem $archivos): int
    {
        $destino = $this->option('destino') ?: resource_path('views/flux/icon');
        $nombres = (array) $this->argument('nombres');

        if ($this->option('actualizar')) {
            $nombres = [...$nombres, ...$this->generados($archivos, $destino)];
        }

        /*
         * Cada pedido es «archivo=origen» o solo «origen». El alias permite
         * guardar un icono de Hugeicons con el nombre que Flux usa por dentro
         * (el ojo de la contraseña, el check de la casilla) para reemplazarlo.
         */
        $pedidos = [];

        foreach ($nombres as $nombre) {
            [$archivo, $origen] = str_contains($nombre, '=') ? explode('=', $nombre, 2) : [$nombre, $nombre];
            $pedidos[$this->aKebab($archivo)] = $this->aKebab($origen);
        }

        if ($pedidos === []) {
            $this->components->error('Indica los íconos a traer, por ejemplo: php artisan pj:iconos home-01 user-group');

            return self::FAILURE;
        }

        $archivos->ensureDirectoryExists($destino);
        $fallidos = 0;

        foreach ($pedidos as $archivo => $origen) {
            if ($this->chocaConHeroicon($archivo) && ! $this->option('forzar')) {
                $this->components->warn("«{$archivo}» ya es un ícono de Heroicons; reemplazarlo cambiaría todas las pantallas que lo usan (incluido el acceso). Usa --forzar si es a propósito.");
                $fallidos++;

                continue;
            }

            $respuesta = Http::timeout(20)->retry(2, 500, throw: false)
                ->get(sprintf(self::URL, self::VERSION, $this->aExportacion($origen)));

            $elementos = $respuesta->successful() ? $this->elementos($respuesta->body()) : [];

            if ($elementos === []) {
                $this->components->error("No se encontró «{$origen}» en Hugeicons Free ".self::VERSION.'.');
                $fallidos++;

                continue;
            }

            $archivos->put("{$destino}/{$archivo}.blade.php", $this->blade($origen, $elementos));
            $this->components->info($archivo === $origen ? "Ícono listo: {$archivo}" : "Ícono listo: {$archivo} (Hugeicons «{$origen}»)");
        }

        return $fallidos === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * «Home01Icon» y «home-01» terminan en el mismo archivo home-01.blade.php.
     */
    private function aKebab(string $nombre): string
    {
        if (! str_ends_with($nombre, 'Icon')) {
            return Str::lower(trim($nombre));
        }

        return Str::of(Str::beforeLast($nombre, 'Icon'))
            ->replaceMatches('/(?<=[a-z])(?=[A-Z0-9])|(?<=[0-9])(?=[A-Z])/', '-')
            ->lower()
            ->value();
    }

    private function aExportacion(string $kebab): string
    {
        return Str::of($kebab)->explode('-')->map(fn (string $parte) => ucfirst($parte))->implode('').'Icon';
    }

    private function chocaConHeroicon(string $nombre): bool
    {
        return file_exists(base_path("vendor/livewire/flux/stubs/resources/views/flux/icon/{$nombre}.blade.php"));
    }

    /**
     * Pedidos «archivo=origen» de los iconos ya generados, leidos del
     * comentario que cada archivo lleva en la cabecera.
     *
     * @return list<string>
     */
    private function generados(Filesystem $archivos, string $destino): array
    {
        if (! $archivos->isDirectory($destino)) {
            return [];
        }

        $pedidos = [];

        foreach ($archivos->glob("{$destino}/*.blade.php") as $ruta) {
            if (preg_match('/Hugeicons Free \S+ · «([a-z0-9-]+)»/u', (string) file_get_contents($ruta), $origen) === 1) {
                $pedidos[] = basename($ruta, '.blade.php').'='.$origen[1];
            }
        }

        return $pedidos;
    }

    /**
     * El modulo exporta una lista de elementos SVG: [["path", { d: "…", strokeWidth: "1.5" }], …].
     *
     * @return list<array{0: string, 1: array<string, string>}>
     */
    private function elementos(string $modulo): array
    {
        preg_match_all('/\[\s*"([a-z]+)",\s*\{(.*?)\}\s*\]/s', $modulo, $coincidencias, PREG_SET_ORDER);

        $elementos = [];

        foreach ($coincidencias as [, $etiqueta, $cuerpo]) {
            preg_match_all('/([A-Za-z]+):\s*"((?:[^"\\\\]|\\\\.)*)"/', $cuerpo, $pares, PREG_SET_ORDER);

            $atributos = [];

            foreach ($pares as [, $clave, $valor]) {
                if ($clave === 'key') {
                    continue;
                }

                $atributos[Str::kebab($clave)] = $valor;
            }

            $elementos[] = [$etiqueta, $atributos];
        }

        return $elementos;
    }

    /**
     * El grosor del trazo sale de cada elemento y pasa a la raiz del SVG, para
     * poder ajustarlo segun la variante: los iconos estan dibujados a 1.5 en
     * una reticula de 24 y a 16 px quedan demasiado finos junto al texto.
     *
     * @param  list<array{0: string, 1: array<string, string>}>  $elementos
     */
    private function blade(string $nombre, array $elementos): string
    {
        $cuerpo = '';

        foreach ($elementos as [$etiqueta, $atributos]) {
            unset($atributos['stroke-width']);

            $html = implode(' ', array_map(
                fn (string $clave, string $valor): string => $clave.'="'.e($valor).'"',
                array_keys($atributos),
                $atributos,
            ));

            $cuerpo .= "    <{$etiqueta} {$html} />\n";
        }

        $version = self::VERSION;

        return <<<BLADE
        @blaze(fold: true)

        {{-- Hugeicons Free {$version} · «{$nombre}» · MIT, Copyright (c) 2025 Hugeicons · generado con `php artisan pj:iconos` --}}

        @props([
            'variant' => 'outline',
        ])

        @php
        \$classes = Flux::classes('shrink-0')
            ->add(match (\$variant) {
                'mini' => '[:where(&)]:size-5',
                'micro' => '[:where(&)]:size-4',
                default => '[:where(&)]:size-6',
            });

        \$strokeWidth = match (\$variant) {
            'solid', 'micro' => 2,
            'mini' => 1.75,
            default => 1.5,
        };
        @endphp

        <svg
            {{ \$attributes->class(\$classes) }}
            data-flux-icon
            data-hugeicon
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke-width="{{ \$strokeWidth }}"
            aria-hidden="true"
            data-slot="icon"
        >
        {$cuerpo}</svg>

        BLADE;
    }
}
