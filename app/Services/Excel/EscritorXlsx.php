<?php

namespace App\Services\Excel;

use RuntimeException;
use ZipArchive;

/**
 * Escritor minimo de archivos .xlsx con una sola hoja.
 *
 * Es la contraparte de LectorXlsx: el proyecto no tiene una libreria de hojas
 * de calculo, asi que el paquete OOXML se arma a mano. Todas las celdas se
 * guardan como texto con formato «@», para que Excel no convierta un DNI como
 * 01234567 en el numero 1234567 ni al abrirlo ni al editarlo. El texto va en
 * `sharedStrings.xml`, que es como lo escribe el propio Excel y lo que mejor
 * entienden los programas que importan hojas de calculo.
 */
class EscritorXlsx
{
    /** @var list<string> */
    private array $cabecera = [];

    /** @var list<float> */
    private array $anchos = [];

    /** @var list<list<string>> */
    private array $filas = [];

    /** @var list<string> */
    private array $textos = [];

    /** @var array<string, int> */
    private array $indiceDeTexto = [];

    public function __construct(private readonly string $nombreDeHoja = 'Hoja1')
    {
        if ($nombreDeHoja === '' || mb_strlen($nombreDeHoja) > 31 || preg_match('/[\\\\\/?*\[\]:]/', $nombreDeHoja) === 1) {
            throw new RuntimeException("El nombre de hoja «{$nombreDeHoja}» no es válido en Excel.");
        }
    }

    /**
     * Primera fila, en negrita y fija al desplazarse.
     *
     * @param  list<string>  $titulos
     * @param  list<float>  $anchos  ancho de cada columna, en caracteres
     */
    public function cabecera(array $titulos, array $anchos = []): static
    {
        $this->cabecera = $titulos;
        $this->anchos = $anchos;

        return $this;
    }

    /**
     * @param  list<string>  $valores
     */
    public function fila(array $valores): static
    {
        $this->filas[] = $valores;

        return $this;
    }

    public function escribir(string $ruta): void
    {
        $hoja = $this->xmlDeHoja();

        $zip = new ZipArchive;

        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->xmlDeTipos());
        $zip->addFromString('_rels/.rels', $this->xmlDeRelacionRaiz());
        $zip->addFromString('xl/workbook.xml', $this->xmlDeLibro());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xmlDeRelacionesDelLibro());
        $zip->addFromString('xl/styles.xml', $this->xmlDeEstilos());
        $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
        $zip->addFromString('xl/sharedStrings.xml', $this->xmlDeTextoCompartido());
        $zip->close();
    }

    private function xmlDeHoja(): string
    {
        $filas = $this->cabecera === [] ? $this->filas : [$this->cabecera, ...$this->filas];
        $columnas = max(1, ...array_map('count', $filas ?: [[]]));
        $ultima = self::letraDeColumna($columnas - 1).max(1, count($filas));

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            ."<dimension ref=\"A1:{$ultima}\"/>"
            .'<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            .($this->cabecera === [] ? '' : '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>')
            .'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>';

        if ($this->anchos !== []) {
            $xml .= '<cols>';

            foreach ($this->anchos as $indice => $ancho) {
                $numero = $indice + 1;
                $xml .= "<col min=\"{$numero}\" max=\"{$numero}\" width=\"{$ancho}\" customWidth=\"1\"/>";
            }

            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($filas as $indice => $valores) {
            $numeroDeFila = $indice + 1;
            $estilo = $this->cabecera !== [] && $indice === 0 ? 2 : 1;
            $xml .= "<row r=\"{$numeroDeFila}\">";

            foreach ($valores as $columna => $valor) {
                $referencia = self::letraDeColumna($columna).$numeroDeFila;
                $xml .= "<c r=\"{$referencia}\" t=\"s\" s=\"{$estilo}\"><v>{$this->texto($valor)}</v></c>";
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function texto(string $valor): int
    {
        /* Caracteres de control que XML no admite: harian el archivo ilegible. */
        $valor = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $valor);

        if (! isset($this->indiceDeTexto[$valor])) {
            $this->indiceDeTexto[$valor] = count($this->textos);
            $this->textos[] = $valor;
        }

        return $this->indiceDeTexto[$valor];
    }

    private function xmlDeTextoCompartido(): string
    {
        $total = count($this->textos);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            ."<sst xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" count=\"{$total}\" uniqueCount=\"{$total}\">";

        foreach ($this->textos as $texto) {
            $xml .= '<si><t xml:space="preserve">'.self::escapar($texto).'</t></si>';
        }

        return $xml.'</sst>';
    }

    /**
     * Estilo 0: el de Excel por defecto. Estilo 1: texto («@»). Estilo 2: texto
     * en negrita sobre gris, para la cabecera.
     */
    private function xmlDeEstilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE7E5E4"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="49" fontId="1" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function xmlDeLibro(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::escapar($this->nombreDeHoja).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xmlDeRelacionesDelLibro(): string
    {
        $base = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            ."<Relationship Id=\"rId1\" Type=\"{$base}/worksheet\" Target=\"worksheets/sheet1.xml\"/>"
            ."<Relationship Id=\"rId2\" Type=\"{$base}/sharedStrings\" Target=\"sharedStrings.xml\"/>"
            ."<Relationship Id=\"rId3\" Type=\"{$base}/styles\" Target=\"styles.xml\"/>"
            .'</Relationships>';
    }

    private function xmlDeRelacionRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xmlDeTipos(): string
    {
        $tipo = 'application/vnd.openxmlformats-officedocument.spreadsheetml';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            ."<Override PartName=\"/xl/workbook.xml\" ContentType=\"{$tipo}.sheet.main+xml\"/>"
            ."<Override PartName=\"/xl/worksheets/sheet1.xml\" ContentType=\"{$tipo}.worksheet+xml\"/>"
            ."<Override PartName=\"/xl/sharedStrings.xml\" ContentType=\"{$tipo}.sharedStrings+xml\"/>"
            ."<Override PartName=\"/xl/styles.xml\" ContentType=\"{$tipo}.styles+xml\"/>"
            .'</Types>';
    }

    private static function escapar(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function letraDeColumna(int $indice): string
    {
        $letras = '';

        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26).$letras;
        }

        return $letras;
    }
}
