<?php

namespace App\Services\Seleccion;

use App\Enums\TipoImportacion;
use App\Models\Inscripcion;
use App\Models\Proceso;
use App\Models\Puesto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Carga a los postulantes del listado del Anexo 06-A.
 *
 * El anexo publicado no trae el DNI, y sin el no hay como cruzar despues las
 * hojas del examen, asi que el archivo debe tener ademas una columna «DNI».
 *
 * Si el archivo trae tambien el nombre del puesto o su unidad de organizacion,
 * los puestos se sincronizan en la misma carga: se crean los que falten y se
 * les registra la unidad. Asi basta con subir un solo archivo. Sin esas
 * columnas, los puestos tienen que estar cargados antes.
 *
 * Todo se valida antes de escribir. Con una sola observacion no se guarda
 * nada, para no dejar el padron a medias. Volver a subir el archivo actualiza
 * por DNI a quienes ya estaban inscritos.
 */
class ImportadorInscripciones
{
    private const TAMANO_LOTE = 500;

    public function __construct(
        private readonly BitacoraDeImportaciones $bitacora,
        private readonly PuestoService $puestos,
    ) {}

    /**
     * @throws RuntimeException con un mensaje para el usuario cuando el archivo
     *                          no tiene el formato esperado o no se pudo guardar.
     */
    public function importar(Proceso $proceso, string $archivo, ?string $nombreArchivo = null, ?Usuario $usuario = null): ResultadoImportacion
    {
        $anexo = AnexoPostulantes::leer($archivo);

        if (! $anexo->tiene(AnexoPostulantes::DOCUMENTO)) {
            throw new RuntimeException(
                'El archivo no tiene la columna del DNI de los postulantes. Agrega una columna «DNI» junto a «APELLIDOS Y NOMBRES».'
            );
        }

        if (! $anexo->tiene(AnexoPostulantes::NOMBRES)) {
            throw new RuntimeException('El archivo no tiene la columna «APELLIDOS Y NOMBRES».');
        }

        $existentes = Puesto::query()->delProceso($proceso->id_pro)->get()->keyBy('codigo_pue');

        if ($existentes->isEmpty() && ! $anexo->tiene(AnexoPostulantes::PUESTO)) {
            throw new RuntimeException(
                "El proceso {$proceso->codigo_pro} no tiene puestos. Cárgalos antes o sube un archivo con la columna «PUESTO»."
            );
        }

        [$registros, $delArchivo, $errores, $leidas] = $this->validar($anexo, $existentes->all());

        $resultado = $registros === [] || $errores !== []
            ? ResultadoImportacion::rechazada($leidas, $errores)
            : $this->guardar($proceso, $registros, $delArchivo, $leidas);

        $this->bitacora->registrar(
            $proceso,
            TipoImportacion::Inscripciones,
            $archivo,
            $nombreArchivo ?? basename($archivo),
            $resultado,
            $usuario,
        );

        return $resultado;
    }

    /**
     * @param  array<string, Puesto>  $existentes  puestos del proceso por codigo
     * @return array{0: list<array{codigo: string, numero_ins: ?int, documento_ins: string, apellidos_nombres_ins: string}>, 1: PuestosDelArchivo, 2: list<string>, 3: int}
     */
    private function validar(AnexoPostulantes $anexo, array $existentes): array
    {
        $registros = [];
        $errores = [];
        $documentos = [];
        $leidas = 0;
        $delArchivo = new PuestosDelArchivo;

        foreach ($anexo->filas as $fila => $valores) {
            $documento = AnexoPostulantes::normalizarDocumento($valores[AnexoPostulantes::DOCUMENTO]);
            $nombres = mb_strtoupper($valores[AnexoPostulantes::NOMBRES]);
            $codigo = AnexoPostulantes::normalizarCodigo($valores[AnexoPostulantes::CODIGO_PUESTO]);
            $puesto = AnexoPostulantes::normalizarNombreDePuesto($valores[AnexoPostulantes::PUESTO] ?? '');
            $unidad = AnexoPostulantes::unidadDe($valores);
            $numero = $valores[AnexoPostulantes::NUMERO] ?? '';

            /* Filas de pie de pagina: no traen postulante ni puesto. */
            if ($documento === '' && $codigo === '') {
                continue;
            }

            $leidas++;
            $observaciones = [];

            if ($documento === '') {
                $observaciones[] = 'falta el DNI';
            } elseif (! AnexoPostulantes::documentoValido($documento)) {
                $observaciones[] = "el documento «{$documento}» no es un DNI (8 dígitos) ni un carné de extranjería válido";
            } elseif (isset($documentos[$documento])) {
                $observaciones[] = "el DNI {$documento} ya aparece en la fila {$documentos[$documento]}";
            }

            if ($nombres === '') {
                $observaciones[] = 'faltan los apellidos y nombres';
            } elseif (mb_strlen($nombres) > 200) {
                $observaciones[] = 'los apellidos y nombres pasan de 200 caracteres';
            }

            if ($codigo === '') {
                $observaciones[] = 'falta el código de puesto';
            } elseif (isset($existentes[$codigo]) && ! $existentes[$codigo]->estaHabilitado()) {
                $observaciones[] = "el puesto {$codigo} está deshabilitado";
            } elseif (! isset($existentes[$codigo]) && $puesto === '') {
                $observaciones[] = "el puesto {$codigo} no está registrado en el proceso";
            } elseif (mb_strlen($codigo) > 20 || mb_strlen($puesto) > 150) {
                $observaciones[] = "el código o el nombre del puesto {$codigo} es demasiado largo";
            } elseif ($unidad !== null && mb_strlen($unidad) > 150) {
                $observaciones[] = 'el nombre de la unidad de organización pasa de 150 caracteres';
            } elseif (($contradiccion = $delArchivo->anotar($fila, $codigo, $puesto, $unidad)) !== null) {
                $observaciones[] = $contradiccion;
            }

            if ($numero !== '' && ! ctype_digit($numero)) {
                $observaciones[] = "el número de orden «{$numero}» no es un número";
            }

            if ($documento !== '' && ! isset($documentos[$documento])) {
                $documentos[$documento] = $fila;
            }

            if ($observaciones !== []) {
                $errores[] = "Fila {$fila}: ".implode('; ', $observaciones).'.';

                continue;
            }

            $registros[] = [
                'codigo' => $codigo,
                'numero_ins' => $numero === '' ? null : (int) $numero,
                'documento_ins' => $documento,
                'apellidos_nombres_ins' => $nombres,
            ];
        }

        return [$registros, $delArchivo, $errores, $leidas];
    }

    /**
     * @param  list<array{codigo: string, numero_ins: ?int, documento_ins: string, apellidos_nombres_ins: string}>  $registros
     */
    private function guardar(Proceso $proceso, array $registros, PuestosDelArchivo $delArchivo, int $filas): ResultadoImportacion
    {
        try {
            return DB::transaction(function () use ($proceso, $registros, $delArchivo, $filas): ResultadoImportacion {
                $puestos = $this->puestos->sincronizar($proceso, $delArchivo->todos());
                $idsDePuesto = Puesto::query()->delProceso($proceso->id_pro)->pluck('id_pue', 'codigo_pue');

                $existentes = Inscripcion::query()
                    ->delProceso($proceso->id_pro)
                    ->get(['documento_ins', 'id_pue', 'numero_ins', 'apellidos_nombres_ins'])
                    ->keyBy('documento_ins');

                $creados = $actualizados = $sinCambios = 0;
                $ahora = now();
                $filasParaGuardar = [];

                foreach ($registros as $leido) {
                    $registro = [
                        'id_pue' => (int) $idsDePuesto[$leido['codigo']],
                        'numero_ins' => $leido['numero_ins'],
                        'documento_ins' => $leido['documento_ins'],
                        'apellidos_nombres_ins' => $leido['apellidos_nombres_ins'],
                    ];
                    $anterior = $existentes->get($registro['documento_ins']);

                    if ($anterior === null) {
                        $creados++;
                    } elseif ((int) $anterior->id_pue === $registro['id_pue']
                        && $anterior->numero_ins === $registro['numero_ins']
                        && $anterior->apellidos_nombres_ins === $registro['apellidos_nombres_ins']) {
                        $sinCambios++;

                        continue;
                    } else {
                        $actualizados++;
                    }

                    $filasParaGuardar[] = $registro + [
                        'id_pro' => $proceso->id_pro,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ];
                }

                foreach (array_chunk($filasParaGuardar, self::TAMANO_LOTE) as $lote) {
                    Inscripcion::upsert(
                        $lote,
                        ['id_pro', 'documento_ins'],
                        ['id_pue', 'numero_ins', 'apellidos_nombres_ins', 'updated_at'],
                    );
                }

                return new ResultadoImportacion(
                    $filas,
                    $creados,
                    $actualizados,
                    $sinCambios,
                    aplicada: true,
                    nota: $this->notaDePuestos($puestos),
                );
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron guardar las inscripciones. No se modificó la información anterior.');
        }
    }

    /**
     * @param  array{creados: int, actualizados: int, sin_cambios: int}  $puestos
     */
    private function notaDePuestos(array $puestos): ?string
    {
        if ($puestos['creados'] === 0 && $puestos['actualizados'] === 0) {
            return null;
        }

        return "Puestos: {$puestos['creados']} nuevo(s) y {$puestos['actualizados']} actualizado(s) con su nombre o unidad.";
    }
}
