{{--
    Anexo 07: resultados de la evaluación técnica. Como en los documentos que
    publica la Corte, la cabecera (logo, proceso, entidad, régimen) va una sola
    vez al inicio, después los puestos uno tras otro, cada uno con su tabla, y
    al final una sola vez el párrafo de los aptos, la fecha y el comité.

    A4 horizontal, en Arial (la registra FuenteArial desde las fuentes del
    servidor; si no la tiene, sale Helvetica, que tiene las mismas medidas).
    La cabecera de cada tabla se repite si la tabla sigue en otra página.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resultados de la evaluación técnica · {{ $proceso->codigo_pro }}</title>
    <style>
        /*
         * Medidas del Anexo 07 tal como sale del Excel guardado como PDF: Excel
         * reduce la hoja a cerca del 70 % para que entre en la página, así que
         * las letras son más chicas que las de la hoja y los márgenes, amplios.
         */
        @page {
            margin: 1.9cm 2.5cm;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            color: #000;
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
            width: 2.6cm;
        }

        .membrete .logo img {
            width: 1.5cm;
        }

        .anexo {
            padding-top: 0.2cm !important;
            text-align: center;
            font-size: 8.5pt;
            font-weight: bold;
        }

        h1 {
            margin: 0.4cm 0 0;
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
        }

        h1.proceso {
            margin-top: 0.3cm;
        }

        h1.regimen {
            margin-top: 0.8cm;
        }

        h1.titulo {
            margin: 0.45cm 0 0;
        }

        .puesto {
            margin-top: 0.45cm;
        }

        .datos-del-puesto {
            margin: 0 0 0.25cm;
            font-size: 8.5pt;
            line-height: 1.35;
            page-break-inside: avoid;
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
            padding: 2.2pt 3pt;
            line-height: 1.05;
            text-align: center;
            vertical-align: middle;
        }

        table.resultados th {
            background-color: #990000;
            color: #fff;
            font-size: 6pt;
            font-weight: bold;
            line-height: 1.2;
        }

        table.resultados td {
            color: #333300;
            font-size: 6pt;
        }

        table.resultados td.nombres {
            text-align: left;
        }

        table.resultados tr {
            page-break-inside: avoid;
        }

        /*
         * Saltos de página. Nada queda suelto:
         * - los datos del puesto no se separan de su tabla, y la cabecera de
         *   la tabla va con al menos sus tres primeras filas (tr.inicio; Dompdf
         *   solo protege la fila de la cabecera, no la primera de datos);
         * - entre las últimas cuatro filas de cada tabla no se corta (tr.final);
         * - el cierre (párrafo y firma) no se parte ni se separa de la última
         *   tabla: si no entra, pasa a la página siguiente con sus últimas filas.
         */
        table.resultados {
            page-break-before: avoid;
        }

        table.resultados tr.inicio,
        table.resultados tr.final {
            page-break-before: avoid;
        }

        .cierre {
            page-break-before: avoid;
            page-break-inside: avoid;
        }

        .pie {
            margin: 0.45cm 0 0;
            font-size: 7.2pt;
            line-height: 1.45;
        }

        .pie .resaltado {
            color: #ff0000;
        }

        .firma {
            margin: 0.35cm 0 0;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.45;
        }
    </style>
</head>
<body>
    {{-- Logo sin transparencia y a tamaño de impresión: Dompdf procesa lento los PNG con canal alfa. --}}
    <table class="membrete">
        <tr>
            <td class="logo"><img src="{{ public_path('img/pj-logo-reporte.jpg') }}" alt="Poder Judicial del Perú"></td>
            <td class="anexo">ANEXO N.° 07</td>
            <td class="logo"></td>
        </tr>
    </table>

    <h1 class="proceso">{{ mb_strtoupper($proceso->nombre_pro) }}</h1>
    <h1>{{ mb_strtoupper($proceso->entidad_pro) }}</h1>
    @if ($proceso->regimen_pro)
        <h1 class="regimen">{{ mb_strtoupper($proceso->regimen_pro) }}</h1>
    @endif
    <h1 class="titulo">RESULTADOS DE LA EVALUACIÓN TÉCNICA</h1>

    @foreach ($resultados as $delPuesto)
        <div class="puesto">
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
                        <tr @class(['inicio' => $loop->index < 3, 'final' => $loop->remaining < 3])>
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

            {{--
                El cierre va una sola vez, pero dentro del último puesto y justo
                después de su tabla: así, si no entra, Dompdf lo lleva a la página
                siguiente junto con las últimas filas de esa tabla.
            --}}
            @if ($loop->last)
                <div class="cierre">
                    <p class="pie">
                        Los postulantes con puntaje de evaluación técnica mayor o igual a {{ $proceso->puntajeMinimoTexto() }} puntos,
                        deben remitir día <span class="resaltado">{{ $proceso->fecha_limite_documentos_pro?->format('d/m/Y') }}</span>
                        hasta las 23:59 horas al <span class="resaltado">correo electrónico <strong>{{ $proceso->correo_documentos_pro }}</strong></span>
                        el reporte de postulación, las imágenes del documento de identidad y la documentación que sustenta los
                        registros realizados al momento de la postulación, así como la <strong>Declaración Jurada que figura como
                        anexo único en las bases del proceso, la cual debe ser debidamente llenada, suscrita y presentada.</strong>
                    </p>

                    <p class="firma">
                        {{ $proceso->lugarYFechaDeResultados() }}<br>
                        El {{ $proceso->comite_pro?->etiqueta() }}
                    </p>
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
