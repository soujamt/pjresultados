<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    <x-marca.iconos />

    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    @vite (['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @fluxAppearance
</head>
<body class="min-h-screen bg-white antialiased dark:bg-zinc-950">
    <div class="flex min-h-screen">
        {{--
            Panel institucional. Solo desde lg: por debajo de ese ancho el
            formulario ocupa la pantalla completa.
        --}}
        <aside class="relative hidden w-[46%] max-w-3xl shrink-0 flex-col justify-between overflow-hidden bg-pj-900 p-14 lg:flex">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0"
                style="
                    background:
                        radial-gradient(70rem 55rem at 100% -15%, var(--color-pj-600) 0%, transparent 58%),
                        radial-gradient(50rem 50rem at -25% 115%, var(--color-pj-950) 0%, transparent 62%);
                "
            ></div>

            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="
                    background-image: repeating-linear-gradient(135deg, #fff 0px, #fff 1px, transparent 1px, transparent 11px);
                "
            ></div>

            {{-- El isologo como sello al margen, en blanco y desbordado a propósito. --}}
            <x-marca.isologo
                aria-hidden="true"
                alt=""
                class="pointer-events-none absolute -right-20 -bottom-20 h-[24rem] w-auto opacity-[0.06] brightness-0 invert"
            />

            {{--
                El logo oficial va sobre blanco, como en sus piezas impresas: sus
                rojos se pierden directamente sobre el guinda del panel.
            --}}
            <div class="relative">
                <div class="inline-flex rounded-2xl bg-white px-6 py-5 shadow-xl shadow-black/25">
                    <x-marca.logo class="h-24" solo-claro />
                </div>

                <div class="mt-5 text-sm font-semibold tracking-tight text-white">
                    Corte Superior de Justicia de Ucayali
                </div>
                <div class="mt-0.5 text-xs text-white/60">Pucallpa · Ucayali</div>
            </div>

            <div class="relative max-w-md">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/90 ring-1 ring-white/15">
                    <span class="size-1.5 rounded-full bg-pj-300"></span>
                    Comité de Selección de Personal
                </div>

                <h1 class="text-4xl leading-[1.1] font-semibold tracking-tight text-balance text-white">
                    Sistema de Resultados de Evaluación
                </h1>

                <p class="mt-5 text-sm leading-relaxed text-pretty text-white/70">
                    Registro de procesos de selección, puestos y postulantes, carga de las evaluaciones
                    técnicas y publicación de resultados.
                </p>

                <p class="mt-12 text-xs text-white/45">
                    &copy; {{ now()->year }} Corte Superior de Justicia de Ucayali
                </p>
            </div>
        </aside>

        <main class="relative flex flex-1 flex-col px-6 py-8 sm:px-10">
            <div class="flex justify-end">
                <flux:button
                    x-data
                    x-on:click="$flux.dark = !$flux.dark"
                    icon="moon"
                    icon:variant="outline"
                    variant="ghost"
                    size="sm"
                    class="text-zinc-500 dark:text-zinc-400"
                    aria-label="Cambiar entre modo claro y oscuro"
                />
            </div>

            <div class="flex flex-1 items-center justify-center py-8">
                <div class="w-full max-w-sm">
                    {{-- La marca se repite aqui solo donde el panel no se ve. --}}
                    <div class="mb-10 flex flex-col items-center gap-4 lg:hidden">
                        <x-marca.logo class="h-28" />
                        <div class="text-center">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                Corte Superior de Justicia de Ucayali
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Sistema de Resultados de Evaluación</div>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </div>

            <p class="text-center text-xs text-zinc-400 lg:hidden dark:text-zinc-500">
                &copy; {{ now()->year }} Corte Superior de Justicia de Ucayali
            </p>
        </main>
    </div>

    @persist ('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @livewireScripts
    @fluxScripts
</body>
</html>
