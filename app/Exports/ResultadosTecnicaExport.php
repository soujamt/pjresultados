<?php

namespace App\Exports;

use App\Models\Proceso;
use App\Services\Evaluacion\ResultadoDePuesto;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Anexo 07 en Excel, igual que el PDF: una sola hoja con la cabecera una vez
 * (logo, proceso, entidad, régimen), los puestos uno tras otro con su tabla, y
 * al final el párrafo de los aptos, la fecha y el comité. Sirve por si el
 * comité necesita retocar algo antes de publicar.
 *
 * Copia las medidas del formato oficial: la columna A queda como margen y las
 * tablas van de B a H. Todo en Arial.
 */
class ResultadosTecnicaExport implements Export, WithDrawings, WithEvents, WithTitle
{
    private const FUENTE = 'Arial';

    private const GUINDA = '990000';

    /** Color del texto de las filas en el formato oficial. */
    private const TEXTO = '333300';

    private const ROJO = 'FF0000';

    /** Fila donde empieza el primer puesto, debajo de la cabecera del documento. */
    private const PRIMER_PUESTO = 11;

    /** @var array<string, int|float> ancho de cada columna, en caracteres */
    private const ANCHOS = ['A' => 13.71, 'B' => 4, 'C' => 36.71, 'D' => 10.86, 'E' => 15.71, 'F' => 19, 'G' => 19.86, 'H' => 55];

    /**
     * @param  list<ResultadoDePuesto>  $resultados
     */
    public function __construct(
        private readonly Proceso $proceso,
        private readonly array $resultados,
    ) {}

    public function title(): string
    {
        return 'Anexo 07';
    }

    public function drawings(): Drawing
    {
        $logo = new Drawing;
        $logo->setName('Poder Judicial del Perú');
        $logo->setPath(public_path('img/pj-logo.png'));
        $logo->setHeight(59);
        $logo->setCoordinates('B1');

        return $logo;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $evento) => $this->dibujar($evento->sheet->getDelegate()),
        ];
    }

    private function dibujar(Worksheet $hoja): void
    {
        $hoja->setShowGridlines(false);
        $hoja->getParent()?->getDefaultStyle()->getFont()->setName(self::FUENTE)->setSize(10);

        foreach (self::ANCHOS as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        $this->titulo($hoja, 1, 'ANEXO N.° 07', 12, 15.75);
        $this->titulo($hoja, 4, mb_strtoupper($this->proceso->nombre_pro), 14, 17.25);
        $this->titulo($hoja, 5, mb_strtoupper($this->proceso->entidad_pro), 14, 53.25);
        $this->titulo($hoja, 7, mb_strtoupper((string) $this->proceso->regimen_pro), 14, 27.75);
        $this->titulo($hoja, 9, 'RESULTADOS DE LA EVALUACIÓN TÉCNICA', 14, 26.25);

        $fila = self::PRIMER_PUESTO;

        foreach ($this->resultados as $delPuesto) {
            $ultima = $this->puesto($hoja, $fila, $delPuesto);
            $fila = $ultima + 2;
        }

        $this->pie($hoja, $fila);
        $this->configurarImpresion($hoja, $fila + 2);
    }

    private function titulo(Worksheet $hoja, int $fila, string $texto, int $tamano, float $alto): void
    {
        $hoja->mergeCells("B{$fila}:H{$fila}");
        $hoja->setCellValue("B{$fila}", $texto);
        $hoja->getRowDimension($fila)->setRowHeight($alto);
        $hoja->getStyle("B{$fila}")->applyFromArray([
            'font' => ['name' => self::FUENTE, 'size' => $tamano, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
    }

    /**
     * Datos del puesto, una fila en blanco y su tabla.
     *
     * @return int la ultima fila de la tabla
     */
    private function puesto(Worksheet $hoja, int $fila, ResultadoDePuesto $delPuesto): int
    {
        $puesto = $delPuesto->puesto;
        $this->datoDelPuesto($hoja, $fila, 'Puesto:', $puesto->nombre_pue);
        $this->datoDelPuesto($hoja, $fila + 1, 'Código del puesto:', $puesto->codigo_pue);
        $this->datoDelPuesto($hoja, $fila + 2, 'Unidad de organización:', (string) $puesto->unidad?->nombre_uni);

        return $this->tabla($hoja, $fila + 4, $delPuesto);
    }

    private function datoDelPuesto(Worksheet $hoja, int $fila, string $etiqueta, string $valor): void
    {
        $texto = new RichText;
        $texto->createTextRun($etiqueta.' ')->getFont()?->setName(self::FUENTE)->setSize(12)->setBold(true);
        $texto->createTextRun($valor)->getFont()?->setName(self::FUENTE)->setSize(12);

        $hoja->mergeCells("B{$fila}:H{$fila}");
        $hoja->setCellValue("B{$fila}", $texto);
        $hoja->getRowDimension($fila)->setRowHeight(mb_strlen($etiqueta.$valor) > 110 ? 33 : 19.5);
        $hoja->getStyle("B{$fila}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }

    /**
     * @return int la ultima fila de la tabla
     */
    private function tabla(Worksheet $hoja, int $cabecera, ResultadoDePuesto $delPuesto): int
    {
        $hoja->fromArray([[
            'N.º',
            'APELLIDOS Y NOMBRES',
            'NOTA OBTENIDA',
            "NOTA PARCIAL\n(Nota obtenida *20/30)\n(De mayor a menor)",
            "PUNTAJE DE EVALUACIÓN TÉCNICA\n(Nota parcial * 0.3)\n(De mayor a menor)",
            "CONDICIÓN\n(APTO - NO APTO)",
            'OBSERVACIONES',
        ]], null, "B{$cabecera}");
        $hoja->getRowDimension($cabecera)->setRowHeight(58.5);
        $hoja->getStyle("B{$cabecera}:H{$cabecera}")->applyFromArray([
            'font' => ['name' => self::FUENTE, 'size' => 8, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GUINDA]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $fila = $cabecera;

        foreach ($delPuesto->filas as $resultado) {
            $fila++;
            $hoja->fromArray([[
                $resultado->numero,
                $resultado->inscripcion->apellidos_nombres_ins,
                $resultado->nota,
                round($resultado->notaParcial, 2),
                $resultado->puntaje,
                $resultado->condicion->etiqueta(),
                (string) $resultado->observacion,
            ]], null, "B{$fila}", true);
            $hoja->getRowDimension($fila)->setRowHeight(mb_strlen((string) $resultado->observacion) > 75 ? 30 : 19.5);
        }

        $primera = $cabecera + 1;
        $hoja->getStyle("B{$primera}:H{$fila}")->applyFromArray([
            'font' => ['name' => self::FUENTE, 'size' => 8, 'color' => ['rgb' => self::TEXTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $hoja->getStyle("C{$primera}:C{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $hoja->getStyle("E{$primera}:F{$fila}")->getNumberFormat()->setFormatCode('0.00');
        $hoja->getStyle("B{$cabecera}:H{$fila}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $fila;
    }

    private function pie(Worksheet $hoja, int $fila): void
    {
        $texto = new RichText;
        $tramos = [
            ['Los postulantes con puntaje de evaluación técnica mayor o igual a '.$this->proceso->puntajeMinimoTexto().' puntos, deben remitir día ', null, false],
            [(string) $this->proceso->fecha_limite_documentos_pro?->format('d/m/Y'), self::ROJO, false],
            [' hasta las 23:59 horas al ', null, false],
            ['correo electrónico', self::ROJO, false],
            [' ', null, false],
            [(string) $this->proceso->correo_documentos_pro, self::ROJO, true],
            [' el reporte de postulación, las imágenes del documento de identidad y la documentación que sustenta los registros realizados al momento de la postulación, así como la ', null, false],
            ['Declaración Jurada que figura como anexo único en las bases del proceso, la cual debe ser debidamente llenada, suscrita y presentada.', null, true],
        ];

        foreach ($tramos as [$contenido, $color, $negrita]) {
            $fuente = $texto->createTextRun($contenido)->getFont();
            $fuente?->setName(self::FUENTE)->setSize(10)->setBold($negrita);

            if ($color !== null) {
                $fuente?->getColor()->setRGB($color);
            }
        }

        $hoja->mergeCells("B{$fila}:H{$fila}");
        $hoja->setCellValue("B{$fila}", $texto);
        $hoja->getRowDimension($fila)->setRowHeight(51);
        $hoja->getStyle("B{$fila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $firma = $fila + 2;
        $hoja->mergeCells("B{$firma}:H{$firma}");
        $hoja->setCellValue("B{$firma}", $this->proceso->lugarYFechaDeResultados()."\nEl ".$this->proceso->comite_pro?->etiqueta());
        $hoja->getRowDimension($firma)->setRowHeight(30);
        $hoja->getStyle("B{$firma}")->applyFromArray([
            'font' => ['name' => self::FUENTE, 'size' => 11, 'bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        ]);
    }

    /**
     * A4 horizontal con los margenes del formato, todo el ancho en una
     * pagina y el numero de pagina abajo a la derecha.
     */
    private function configurarImpresion(Worksheet $hoja, int $ultimaFila): void
    {
        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea("A1:H{$ultimaFila}");

        $hoja->getPageMargins()->setLeft(0.3 / 2.54)->setRight(0.3 / 2.54)->setTop(1.9 / 2.54)->setBottom(1.9 / 2.54);
        $hoja->getHeaderFooter()->setOddFooter('&R&8Página &P de &N');
        $hoja->setSelectedCell('A1');
    }
}
