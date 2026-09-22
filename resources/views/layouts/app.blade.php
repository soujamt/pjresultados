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
<body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
    @php
        $accesos = app(App\Services\Auth\AccesoService::class);
        $usuario = auth()->user();
    @endphp

    <flux:sidebar sticky collapsible class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            {{--
                El contenedor del logo de Flux mide 24px y recorta lo que
                sobra; las clases del slot lo agrandan y quitan el redondeo,
                que se comeria las esquinas del isologo.
            --}}
            <flux:sidebar.brand :href="route('inicio')" name="Resultados PJ">
                <x-slot:logo class="h-8 min-w-9 rounded-none">
                    <x-marca.isologo class="h-8 w-auto" />
                </x-slot:logo>
            </flux:sidebar.brand>

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
            />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" :href="route('inicio')" :current="request()->routeIs('inicio')" wire:navigate>
                Inicio
            </flux:sidebar.item>

            @if ($accesos->puedeAlguno($usuario, [
                App\Enums\Permiso::ProcesosVer,
                App\Enums\Permiso::UnidadesVer,
                App\Enums\Permiso::PuestosVer,
                App\Enums\Permiso::InscripcionesVer,
            ]))
                <flux:sidebar.group heading="Proceso de selección" class="mt-2">
                    @can(App\Enums\Permiso::ProcesosVer->value)
                        <flux:sidebar.item icon="calendar-days" :href="route('seleccion.procesos')" :current="request()->routeIs('seleccion.procesos')" wire:navigate>
                            Procesos
                        </flux:sidebar.item>
                    @endcan

                    @can(App\Enums\Permiso::UnidadesVer->value)
                        <flux:sidebar.item icon="building-office-2" :href="route('seleccion.unidades')" :current="request()->routeIs('seleccion.unidades')" wire:navigate>
                            Unidades
                        </flux:sidebar.item>
                    @endcan

                    @can(App\Enums\Permiso::PuestosVer->value)
                        <flux:sidebar.item icon="briefcase" :href="route('seleccion.puestos')" :current="request()->routeIs('seleccion.puestos')" wire:navigate>
                            Puestos
                        </flux:sidebar.item>
                    @endcan

                    @can(App\Enums\Permiso::InscripcionesVer->value)
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('seleccion.inscripciones')" :current="request()->routeIs('seleccion.inscripciones')" wire:navigate>
                            Inscripciones
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endif

            @if ($accesos->puedeAlguno($usuario, [App\Enums\Permiso::ExamenesVer, App\Enums\Permiso::ResultadosVer]))
                <flux:sidebar.group heading="Evaluación" class="mt-2">
                    @can(App\Enums\Permiso::ExamenesVer->value)
                        <flux:sidebar.item icon="document-arrow-up" :href="route('evaluacion.examenes')" :current="request()->routeIs('evaluacion.examenes')" wire:navigate>
                            Exámenes
                        </flux:sidebar.item>
                    @endcan

                    @can(App\Enums\Permiso::ResultadosVer->value)
                        <flux:sidebar.item icon="chart-bar-square" :href="route('evaluacion.resultados')" :current="request()->routeIs('evaluacion.resultados')" wire:navigate>
                            Resultados
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endif

            @if ($accesos->puedeAlguno($usuario, [App\Enums\Permiso::UsuariosVer, App\Enums\Permiso::RolesVer]))
                <flux:sidebar.group heading="Seguridad" class="mt-2">
                    @can(App\Enums\Permiso::UsuariosVer->value)
                        <flux:sidebar.item icon="users" :href="route('seguridad.usuarios')" :current="request()->routeIs('seguridad.usuarios')" wire:navigate>
                            Usuarios
                        </flux:sidebar.item>
                    @endcan

                    @can(App\Enums\Permiso::RolesVer->value)
                        <flux:sidebar.item icon="shield-check" :href="route('seguridad.roles')" :current="request()->routeIs('seguridad.roles')" wire:navigate>
                            Roles y permisos
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endif
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <div class="px-2 pb-2 text-xs leading-relaxed text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden dark:text-zinc-500">
            Corte Superior de Justicia de Ucayali
        </div>
    </flux:sidebar>

    <flux:header class="border-b border-zinc-200 bg-white lg:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="me-4 lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:navbar class="me-4">
            <flux:button
                x-data
                x-on:click="$flux.dark = !$flux.dark"
                icon="moon"
                icon:variant="outline"
                variant="subtle"
                aria-label="Cambiar entre modo claro y oscuro"
            />
        </flux:navbar>

        <flux:dropdown position="top" align="end">
            <flux:profile :name="$usuario?->nombre_usu" :initials="$usuario?->iniciales()" aria-label="Menú de la cuenta" />

            <flux:menu>
                <div class="px-2 py-1.5">
                    <div class="text-sm font-medium">{{ $usuario?->nombre_usu }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $usuario?->usuario_usu }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $usuario?->rol?->nombre_rol }}</div>
                </div>

                <flux:menu.separator />

                <form method="POST" action="{{ route('auth.salir') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        Salir
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main>
        {{ $slot }}
    </flux:main>

    @persist ('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @livewireScripts
    @fluxScripts
</body>
</html>
