<?php

namespace App\Services\Reportes;

use Dompdf\Dompdf;

/**
 * Registra en Dompdf la Arial instalada en el servidor, para que los PDF
 * oficiales salgan en Arial como los formatos del Poder Judicial.
 *
 * Arial es de Microsoft y no se puede redistribuir: no va en el repositorio.
 * Dompdf copia la fuente a su cache (storage/fonts, fuera de git) la primera
 * vez; despues solo se comprueba que ya este registrada.
 */
class FuenteArial
{
    /** Carpetas de fuentes de Windows, Linux (ttf-mscorefonts) y macOS. */
    private const CARPETAS = [
        'C:/Windows/Fonts',
        '/usr/share/fonts/truetype/msttcorefonts',
        '/usr/share/fonts/msttcore',
        '/usr/share/fonts/TTF',
        '/Library/Fonts',
        '/System/Library/Fonts/Supplemental',
    ];

    /** Como se llaman los archivos en cada sistema. */
    private const ARCHIVOS = [
        'normal' => ['arial.ttf', 'Arial.ttf', 'ARIAL.TTF'],
        'negrita' => ['arialbd.ttf', 'Arial_Bold.ttf', 'Arial Bold.ttf', 'ARIALBD.TTF'],
    ];

    /**
     * Rutas de Arial normal y negrita. Si se configuro PDF_FUENTE_ARIAL solo se
     * busca en esa carpeta; si no, en las carpetas de fuentes conocidas.
     *
     * @return ?array{normal: string, negrita: string}
     */
    public function ubicar(): ?array
    {
        $configurada = config('app.fuente_arial');
        $carpetas = filled($configurada) ? [(string) $configurada] : self::CARPETAS;

        foreach ($carpetas as $carpeta) {
            $normal = self::buscar($carpeta, self::ARCHIVOS['normal']);
            $negrita = self::buscar($carpeta, self::ARCHIVOS['negrita']);

            if ($normal !== null && $negrita !== null) {
                return ['normal' => $normal, 'negrita' => $negrita];
            }
        }

        return null;
    }

    /**
     * Devuelve false si el servidor no tiene Arial: el PDF sale igual, con
     * Helvetica, que tiene las mismas medidas.
     */
    public function registrar(Dompdf $dompdf): bool
    {
        $archivos = $this->ubicar();

        if ($archivos === null) {
            return false;
        }

        $metricas = $dompdf->getFontMetrics();
        $opciones = $dompdf->getOptions();
        $chroot = $opciones->getChroot();

        /*
         * Dompdf solo lee archivos dentro de su chroot (el proyecto). Se abre
         * a la carpeta de fuentes mientras copia Arial a su cache, y se cierra.
         */
        $opciones->setChroot([...$chroot, dirname($archivos['normal']), dirname($archivos['negrita'])]);

        try {
            return $metricas->registerFont(['family' => 'Arial', 'weight' => 'normal', 'style' => 'normal'], $archivos['normal'])
                && $metricas->registerFont(['family' => 'Arial', 'weight' => 'bold', 'style' => 'normal'], $archivos['negrita']);
        } finally {
            $opciones->setChroot($chroot);
        }
    }

    /**
     * @param  list<string>  $nombres
     */
    private static function buscar(string $carpeta, array $nombres): ?string
    {
        foreach ($nombres as $nombre) {
            $ruta = rtrim($carpeta, '/\\').'/'.$nombre;

            if (is_file($ruta) && is_readable($ruta)) {
                return $ruta;
            }
        }

        return null;
    }
}
