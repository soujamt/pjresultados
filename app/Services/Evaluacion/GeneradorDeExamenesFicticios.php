<?php

namespace App\Services\Evaluacion;

use App\Models\Inscripcion;
use App\Models\Proceso;
use Random\Engine\Mt19937;
use Random\Engine\Secure;
use Random\Randomizer;
use RuntimeException;

/**
 * Arma un .txt identico al que exporta la lectora optica, con calificaciones
 * inventadas para los inscritos del proceso. Sirve para ensayar la carga de
 * examenes y los resultados antes del examen real.
 *
 * Cada postulante recibe su propia probabilidad de acertar (entre 20 % y
 * 90 %), asi los puntajes salen repartidos como en un examen de verdad. Lo que
 * no acierta queda en su mayoria como error, y a veces en blanco o con doble
 * marca. La nota es igual a los aciertos, como la entrega la lectora.
 */
class GeneradorDeExamenesFicticios
{
    public const PREGUNTAS = 30;

    /** La hoja de la lectora trae 100 casilleros, aunque el examen use 30. */
    private const CASILLEROS = 100;

    private const CABECERA = 'NRO DE DNI;APELLIDOS Y NOMBRES;Nota 30;Aciertos;Errores;Blancos;Dobles;RESPUESTAS;';

    private const LETRAS = ['A', 'B', 'C', 'D', 'E'];

    /**
     * @param  int  $faltantes  inscritos que no se presentaron; se eligen al azar
     * @param  ?int  $semilla  para obtener siempre el mismo archivo
     * @return array{contenido: string, inscritos: int, puntajes: list<int>, faltantes: list<Inscripcion>}
     *
     * @throws RuntimeException si no hay inscritos o los faltantes no dejan ninguna hoja.
     */
    public function generar(Proceso $proceso, int $faltantes, ?int $semilla = null): array
    {
        $inscritos = Inscripcion::query()
            ->delProceso($proceso->id_pro)
            ->orderBy('id_ins')
            ->get(['id_ins', 'documento_ins', 'apellidos_nombres_ins'])
            ->all();

        if ($inscritos === []) {
            throw new RuntimeException("El proceso {$proceso->codigo_pro} no tiene inscritos.");
        }

        if ($faltantes < 0 || $faltantes >= count($inscritos)) {
            throw new RuntimeException(
                'Los faltantes tienen que ser entre 0 y '.(count($inscritos) - 1).': el proceso tiene '.count($inscritos).' inscritos.'
            );
        }

        $azar = new Randomizer($semilla === null ? new Secure : new Mt19937($semilla));

        /* El orden mezclado imita el orden en que se pasan las hojas por la lectora. */
        $mezclados = $azar->shuffleArray($inscritos);
        $clave = array_map(fn (): string => self::LETRAS[$azar->getInt(0, 4)], range(1, self::PREGUNTAS));

        $lineas = [self::CABECERA];
        $puntajes = [];

        foreach (array_slice($mezclados, $faltantes) as $inscripcion) {
            [$linea, $aciertos] = $this->hoja($inscripcion, $clave, $azar);
            $lineas[] = $linea;
            $puntajes[] = $aciertos;
        }

        $faltan = array_slice($mezclados, 0, $faltantes);
        usort($faltan, fn (Inscripcion $a, Inscripcion $b): int => strcmp($a->apellidos_nombres_ins, $b->apellidos_nombres_ins));

        return [
            'contenido' => mb_convert_encoding(implode("\r\n", $lineas)."\r\n", 'Windows-1252', 'UTF-8'),
            'inscritos' => count($inscritos),
            'puntajes' => $puntajes,
            'faltantes' => $faltan,
        ];
    }

    /**
     * @param  list<string>  $clave  respuesta correcta de cada pregunta
     * @return array{0: string, 1: int} la linea y sus aciertos
     */
    private function hoja(Inscripcion $inscripcion, array $clave, Randomizer $azar): array
    {
        $nivel = $azar->getFloat(0.2, 0.9);
        $aciertos = $errores = $blancos = $dobles = 0;
        $marcas = [];

        foreach ($clave as $correcta) {
            $tirada = $azar->getFloat(0, 1);
            $otra = array_values(array_diff(self::LETRAS, [$correcta]))[$azar->getInt(0, 3)];

            if ($tirada < $nivel) {
                $marcas[] = $correcta;
                $aciertos++;
            } elseif ($tirada < $nivel + 0.06) {
                $marcas[] = ' ';
                $blancos++;
            } elseif ($tirada < $nivel + 0.08) {
                $marcas[] = $correcta.$otra;
                $dobles++;
            } else {
                $marcas[] = $otra;
                $errores++;
            }
        }

        $linea = implode(';', [
            $inscripcion->documento_ins,
            $inscripcion->apellidos_nombres_ins,
            "{$aciertos},00000",
            $aciertos,
            $errores,
            $blancos,
            $dobles,
            ...array_pad($marcas, self::CASILLEROS, ' '),
        ]).';';

        return [$linea, $aciertos];
    }
}
