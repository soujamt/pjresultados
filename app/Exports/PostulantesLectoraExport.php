<?php

namespace App\Exports;

use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Unidad;
use App\Services\Seleccion\InscripcionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Listado de postulantes para la lectora de fichas ópticas.
 *
 * El programa de la lectora corre en otra maquina y solo necesita reconocer a
 * cada postulante por su DNI, asi que el archivo es deliberadamente simple:
 * una hoja, la cabecera en la fila 1 y dos columnas. Respeta los mismos
 * filtros del listado de inscripciones.
 *
 * @implements FromCollection<int, Inscripcion>
 * @implements WithMapping<Inscripcion>
 */
class PostulantesLectoraExport extends DefaultValueBinder implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /** Columna del DNI, la que cruza la lectora. */
    private const COLUMNA_DOCUMENTO = 'A';

    public function __construct(
        private readonly Proceso $proceso,
        private readonly ?Unidad $unidad = null,
        private readonly ?Puesto $puesto = null,
        private readonly string $busqueda = '',
    ) {}

    /**
     * En el mismo orden del listado de la pantalla (por numero de orden).
     *
     * @return Collection<int, Inscripcion>
     */
    public function collection(): Collection
    {
        return app(InscripcionService::class)
            ->consulta([
                'proceso' => $this->proceso->id_pro,
                'unidad' => $this->unidad?->id_uni,
                'puesto' => $this->puesto?->id_pue,
                'busqueda' => $this->busqueda,
            ])
            ->setEagerLoads([])
            ->get(['documento_ins', 'apellidos_nombres_ins']);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['DNI', 'APELLIDOS Y NOMBRES'];
    }

    /**
     * @param  Inscripcion  $fila
     * @return list<string>
     */
    public function map(mixed $fila): array
    {
        return [$fila->documento_ins, $fila->apellidos_nombres_ins];
    }

    public function title(): string
    {
        return 'Postulantes';
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [self::COLUMNA_DOCUMENTO => NumberFormat::FORMAT_TEXT];
    }

    /**
     * @return array<string, float>
     */
    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 55];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $hoja): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE7E5E4']],
            ],
        ];
    }

    /**
     * La cabecera queda fija al desplazarse por las 682 filas.
     *
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $evento) => $evento->sheet->getDelegate()->freezePane('A2'),
        ];
    }

    /**
     * Toda la columna del DNI va como texto. El enlazador por omision la
     * partiria en dos tipos: el DNI que empieza en cero lo deja en texto, pero
     * «72540243» lo escribe como numero. `WithColumnFormatting` solo cambia
     * como se ve la celda, no el tipo con que se guarda, y una columna de
     * busqueda con dos tipos es lo que hace fallar el cruce en la lectora.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getColumn() === self::COLUMNA_DOCUMENTO) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * «postulantes-002-2026-ue-ucayali.xlsx», con la unidad o el puesto al
     * final cuando el listado va filtrado, para no confundir los archivos.
     */
    public function nombreArchivo(): string
    {
        $partes = ['postulantes', $this->proceso->codigo_pro, $this->unidad?->nombre_uni, $this->puesto?->codigo_pue];

        return Str::slug(implode(' ', array_filter($partes))).'.xlsx';
    }
}
