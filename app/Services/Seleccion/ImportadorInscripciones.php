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
 * Los puestos tienen que estar cargados antes: aqui solo se buscan por codigo.
 *
 * Todo se valida antes de escribir. Con una sola observacion no se guarda
 * nada, para no dejar el padron a medias. Volver a subir el archivo actualiza
 * por DNI a quienes ya estaban inscritos.
 */
class ImportadorInscripciones
{
    private const TAMANO_LOTE = 500;

    public function __construct(private readonly BitacoraDeImportaciones $bitacora) {}

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

        $puestos = Puesto::query()->delProceso($proceso->id_pro)->get()->keyBy('codigo_pue');

        if ($puestos->isEmpty()) {
            throw new RuntimeException("El proceso {$proceso->codigo_pro} no tiene puestos. Cárgalos antes que las inscripciones.");
        }

        [$registros, $errores, $leidas] = $this->validar($anexo, $puestos->all());

        $resultado = $registros === [] || $errores !== []
            ? ResultadoImportacion::rechazada($leidas, $errores)
            : $this->guardar($proceso, $registros, $leidas);

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
     * @param  array<string, Puesto>  $puestos
     * @return array{0: list<array{id_pue: int, numero_ins: ?int, documento_ins: string, apellidos_nombres_ins: string}>, 1: list<string>, 2: int}
     */
    private function validar(AnexoPostulantes $anexo, array $puestos): array
    {
        $registros = [];
        $errores = [];
        $documentos = [];
        $leidas = 0;

        foreach ($anexo->filas as $fila => $valores) {
            $documento = AnexoPostulantes::normalizarDocumento($valores[AnexoPostulantes::DOCUMENTO]);
            $nombres = mb_strtoupper($valores[AnexoPostulantes::NOMBRES]);
            $codigo = AnexoPostulantes::normalizarCodigo($valores[AnexoPostulantes::CODIGO_PUESTO]);
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
            } elseif (! isset($puestos[$codigo])) {
                $observaciones[] = "el puesto {$codigo} no está registrado en el proceso";
            } elseif (! $puestos[$codigo]->estaHabilitado()) {
                $observaciones[] = "el puesto {$codigo} está deshabilitado";
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
                'id_pue' => $puestos[$codigo]->id_pue,
                'numero_ins' => $numero === '' ? null : (int) $numero,
                'documento_ins' => $documento,
                'apellidos_nombres_ins' => $nombres,
            ];
        }

        return [$registros, $errores, $leidas];
    }

    /**
     * @param  list<array{id_pue: int, numero_ins: ?int, documento_ins: string, apellidos_nombres_ins: string}>  $registros
     */
    private function guardar(Proceso $proceso, array $registros, int $filas): ResultadoImportacion
    {
        try {
            return DB::transaction(function () use ($proceso, $registros, $filas): ResultadoImportacion {
                $existentes = Inscripcion::query()
                    ->delProceso($proceso->id_pro)
                    ->get(['documento_ins', 'id_pue', 'numero_ins', 'apellidos_nombres_ins'])
                    ->keyBy('documento_ins');

                $creados = $actualizados = $sinCambios = 0;
                $ahora = now();
                $filasParaGuardar = [];

                foreach ($registros as $registro) {
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

                return new ResultadoImportacion($filas, $creados, $actualizados, $sinCambios, aplicada: true);
            }, 3);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('No se pudieron guardar las inscripciones. No se modificó la información anterior.');
        }
    }
}
