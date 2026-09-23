{{--
    Anexo 07: resultados de la evaluación técnica, uno por puesto. Replica el
    formato oficial en Excel (A4 horizontal, Arial, cabecera guinda). Dompdf
    usa Helvetica, que tiene las mismas medidas que Arial y no hay que
    incrustarla. La cabecera de la tabla se repite en cada página.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resultados de la evaluación técnica · {{ $proceso->codigo_pro }}</title>
    <style>
        @page {
            margin: 1.2cm 1.2cm 1.3cm;
        }

        body {
            margin: 0;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #000;
        }

        .puesto + .puesto {
            page-break-before: always;
        }

        .membrete {
            width: 100%;
            border-collapse: collapse;
        }

        .membrete td {
            padding: 0;
            vertical-align: top;
        }

        .membrete .logo {
            width: 3.2cm;
        }

        .membrete .logo img {
            width: 1.7cm;
        }

        .anexo {
            padding-top: 0.25cm !important;
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
        }

        h1 {
            margin: 0 0 0.2cm;
            text-align: center;
            font-size: 12.5pt;
            font-weight: bold;
        }

        h1.regimen {
            margin-top: 0.55cm;
        }

        h1.titulo {
            margin: 0.55cm 0 0.4cm;
        }

        .datos-del-puesto {
            margin: 0 0 0.3cm;
            font-size: 10.5pt;
            line-height: 1.45;
        }

        .datos-del-puesto strong {
            font-weight: bold;
        }

        table.resultados {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.resultados th,
        table.resultados td {
            border: 0.75pt solid #000;
            padding: 2.5pt 3pt;
            line-height: 1.15;
            text-align: center;
            vertical-align: middle;
        }

        table.resultados th {
            background-color: #990000;
            color: #fff;
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.3;
        }

        table.resultados td {
            color: #333300;
            font-size: 7.5pt;
        }

        table.resultados td.nombres {
            text-align: left;
        }

        /* Así «DESCALIFICADO/A - NO ALCANZÓ EL PUNTAJE MÍNIMO APROBATORIO» entra en una línea. */
        table.resultados td.observacion {
            font-size: 7pt;
        }

        table.resultados tr {
            page-break-inside: avoid;
        }

        .pie {
            margin: 0.55cm 0 0;
            font-size: 9.5pt;
            line-height: 1.4;
        }

        .pie .resaltado {
            color: #ff0000;
        }

        .firma {
            margin: 0.45cm 0 0;
            font-size: 10.5pt;
            font-weight: bold;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    @foreach ($resultados as $delPuesto)
        <div class="puesto">
            {{-- Logo sin transparencia y a tamaño de impresión: Dompdf procesa lento los PNG con canal alfa. --}}
            <table class="membrete">
                <tr>
                    <td class="logo"><img src="{{ public_path('img/pj-logo-reporte.jpg') }}" alt="Poder Judicial del Perú"></td>
                    <td class="anexo">ANEXO N.° 07</td>
                    <td class="logo"></td>
                </tr>
            </table>

            <h1>{{ mb_strtoupper($proceso->nombre_pro) }}</h1>
            <h1>{{ mb_strtoupper($proceso->entidad_pro) }}</h1>
            @if ($proceso->regimen_pro)
                <h1 class="regimen">{{ mb_strtoupper($proceso->regimen_pro) }}</h1>
            @endif
            <h1 class="titulo">RESULTADOS DE LA EVALUACIÓN TÉCNICA</h1>

            <div class="datos-del-puesto">
                <div><strong>Puesto:</strong> {{ $delPuesto->puesto->nombre_pue }}</div>
                <div><strong>Código del puesto:</strong> {{ $delPuesto->puesto->codigo_pue }}</div>
                <div><strong>Unidad de organización:</strong> {{ $delPuesto->puesto->unidad?->nombre_uni }}</div>
            </div>

            {{-- Dompdf no respeta <colgroup>: los anchos van en la cabecera. Son las proporciones del Excel oficial. --}}
            <table class="resultados">
                <thead>
                    <tr>
                        <th style="width: 3%">N.º</th>
                        <th style="width: 24%">APELLIDOS Y NOMBRES</th>
                        <th style="width: 6.5%">NOTA OBTENIDA</th>
                        <th style="width: 9.5%">NOTA PARCIAL<br>(Nota obtenida *20/30)<br>(De mayor a menor)</th>
                        <th style="width: 11%">PUNTAJE DE EVALUACIÓN TÉCNICA<br>(Nota parcial * 0.3)<br>(De mayor a menor)</th>
                        <th style="width: 10%">CONDICIÓN<br>(APTO - NO APTO)</th>
                        <th style="width: 36%">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($delPuesto->filas as $fila)
                        <tr>
                            <td>{{ $fila->numero }}</td>
                            <td class="nombres">{{ $fila->inscripcion->apellidos_nombres_ins }}</td>
                            <td>{{ $fila->nota }}</td>
                            <td>{{ $fila->notaParcialTexto() }}</td>
                            <td>{{ $fila->puntajeTexto() }}</td>
                            <td>{{ $fila->condicion->etiqueta() }}</td>
                            <td class="observacion">{{ $fila->observacion }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="pie">
                Los postulantes con puntaje de evaluación técnica mayor o igual a {{ $proceso->puntajeMinimoTexto() }} puntos,
                deben remitir día <span class="resaltado">{{ $proceso->fecha_limite_documentos_pro?->format('d/m/Y') }}</span>
                hasta las 23:59 horas al <span class="resaltado">correo electrónico</span>
                <strong>{{ $proceso->correo_documentos_pro }}</strong> el reporte de postulación, las imágenes del documento de
                identidad y la documentación que sustenta los registros realizados al momento de la postulación, así como la
                <strong>Declaración Jurada que figura como anexo único en las bases del proceso, la cual debe ser debidamente
                llenada, suscrita y presentada.</strong>
            </p>

            <p class="firma">
                {{ $proceso->lugarYFechaDeResultados() }}<br>
                El {{ $proceso->comite_pro?->etiqueta() }}
            </p>
        </div>
    @endforeach
</body>
</html>
