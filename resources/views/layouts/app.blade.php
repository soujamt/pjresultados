<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    <x-marca.iconos />

    @fonts
    @vite (['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
    @fluxAppearance
</head>
@php
    $accesos = app(App\Services\Auth\AccesoService::class);
    $usuario = auth()->user();

    /*
     * Ruta de navegación de la cabecera: la sección del menú y la pantalla.
     * Las secciones no tienen página propia, así que solo se muestran.
     */
    [$seccion, $pantalla] = match (request()->route()?->getName()) {
        'seleccion.procesos' => ['Proceso de selección', 'Procesos'],
        'seleccion.unidades' => ['Proceso de selección', 'Unidades de organización'],
        'seleccion.puestos' => ['Proceso de selección', 'Puestos'],
        'seleccion.inscripciones' => ['Proceso de selección', 'Inscripciones'],
        'evaluacion.examenes' => ['Evaluación', 'Exámenes'],
        'evaluacion.resultados' => ['Evaluación', 'Resultados'],
        'seguridad.usuarios' => ['Seguridad', 'Usuarios'],
        'seguridad.roles' => ['Seguridad', 'Roles y permisos'],
        default => [null, null],
    };
@endphp
<body class="sistema min-h-screen bg-zinc-50 font-institucional text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900">
        <flux:sidebar.header>
            {{--
                El contenedor del logo de Flux mide 24px y recorta lo que
                sobra; las clases del slot lo agrandan y quitan el redondeo,
                que se comería las esquinas del isologo.
            --}}
            <flux:sidebar.brand :href="route('inicio')" name="Resultados PJ" wire:navigate>
                <x-slot:logo class="h-8 min-w-9 rounded-none">
                    <x-marca.isologo class="h-8 w-auto" alt="" />
                </x-slot:logo>
            </flux:sidebar.brand>

            <flux:sidebar.collapse
                class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
            />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home-01" :href="route('inicio')" :current="request()->routeIs('inicio')" wire:navigate>
                Inicio
            </flux:sidebar.item>

            @if ($accesos->puedeAlguno($usuario, [
                App\Enums\Permiso::ProcesosVer,
                App\Enums\Permiso::UnidadesVer,
                App\Enums\Permiso::PuestosVer,
                App\Enums\Permiso::InscripcionesVer,
            ]))
                <x-menu.seccion>Proceso de selección</x-menu.seccion>

                @can(App\Enums\Permiso::ProcesosVer->value)
                    <flux:sidebar.item icon="calendar-03" :href="route('seleccion.procesos')" :current="request()->routeIs('seleccion.procesos')" wire:navigate>
                        Procesos
                    </flux:sidebar.item>
                @endcan

                @can(App\Enums\Permiso::UnidadesVer->value)
                    <flux:sidebar.item icon="building-03" :href="route('seleccion.unidades')" :current="request()->routeIs('seleccion.unidades')" wire:navigate>
                        Unidades
                    </flux:sidebar.item>
                @endcan

                @can(App\Enums\Permiso::PuestosVer->value)
                    <flux:sidebar.item icon="briefcase-01" :href="route('seleccion.puestos')" :current="request()->routeIs('seleccion.puestos')" wire:navigate>
                        Puestos
                    </flux:sidebar.item>
                @endcan

                @can(App\Enums\Permiso::InscripcionesVer->value)
                    <flux:sidebar.item icon="user-list" :href="route('seleccion.inscripciones')" :current="request()->routeIs('seleccion.inscripciones')" wire:navigate>
                        Inscripciones
                    </flux:sidebar.item>
                @endcan
            @endif

            @if ($accesos->puedeAlguno($usuario, [App\Enums\Permiso::ExamenesVer, App\Enums\Permiso::ResultadosVer]))
                <x-menu.seccion>Evaluación</x-menu.seccion>

                @can(App\Enums\Permiso::ExamenesVer->value)
                    <flux:sidebar.item icon="file-upload" :href="route('evaluacion.examenes')" :current="request()->routeIs('evaluacion.examenes')" wire:navigate>
                        Exámenes
                    </flux:sidebar.item>
                @endcan

                @can(App\Enums\Permiso::ResultadosVer->value)
                    <flux:sidebar.item icon="ranking" :href="route('evaluacion.resultados')" :current="request()->routeIs('evaluacion.resultados')" wire:navigate>
                        Resultados
                    </flux:sidebar.item>
                @endcan
            @endif

            @if ($accesos->puedeAlguno($usuario, [App\Enums\Permiso::UsuariosVer, App\Enums\Permiso::RolesVer]))
                <x-menu.seccion>Seguridad</x-menu.seccion>

                @can(App\Enums\Permiso::UsuariosVer->value)
                    <flux:sidebar.item icon="user-multiple" :href="route('seguridad.usuarios')" :current="request()->routeIs('seguridad.usuarios')" wire:navigate>
                        Usuarios
                    </flux:sidebar.item>
                @endcan

                @can(App\Enums\Permiso::RolesVer->value)
                    <flux:sidebar.item icon="shield-user" :href="route('seguridad.roles')" :current="request()->routeIs('seguridad.roles')" wire:navigate>
                        Roles y permisos
                    </flux:sidebar.item>
                @endcan
            @endif
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <div class="border-t border-zinc-100 px-3 pt-4 pb-1 text-xs leading-relaxed text-zinc-500 in-data-flux-sidebar-collapsed-desktop:hidden dark:border-white/10 dark:text-zinc-400">
            <div class="font-medium text-zinc-700 dark:text-zinc-300">Corte Superior de Justicia de Ucayali</div>
            <div>Comité de Selección de Personal</div>
        </div>
    </flux:sidebar>

    <flux:header class="border-b border-zinc-200 bg-white dark:border-white/10 dark:bg-zinc-900">
        <flux:sidebar.toggle class="me-2 lg:hidden" icon="menu-01" inset="left" aria-label="Abrir el menú" />

        @if ($pantalla)
            <nav aria-label="Ruta de navegación" class="flex min-w-0 items-center gap-1.5 text-sm">
                <a href="{{ route('inicio') }}" wire:navigate class="hidden text-zinc-500 transition-colors hover:text-zinc-900 sm:inline dark:text-zinc-400 dark:hover:text-white">
                    Inicio
                </a>
                <flux:icon.arrow-right-01 class="hidden size-3.5 shrink-0 text-zinc-400 sm:block" />
                <span class="hidden truncate text-zinc-500 md:inline dark:text-zinc-400">{{ $seccion }}</span>
                <flux:icon.arrow-right-01 class="hidden size-3.5 shrink-0 text-zinc-400 md:block" />
                <span aria-current="page" class="truncate font-medium text-zinc-900 dark:text-white">{{ $pantalla }}</span>
            </nav>
        @endif

        <flux:spacer />

        <flux:tooltip content="Modo claro u oscuro">
            <flux:button
                x-data
                x-on:click="cambiarSinTransiciones(() => $flux.dark = ! $flux.dark)"
                variant="subtle"
                square
                size="sm"
                class="me-2"
                aria-label="Cambiar entre modo claro y oscuro"
            >
                <flux:icon.moon-02 variant="mini" class="dark:hidden" />
                <flux:icon.sun-03 variant="mini" class="hidden dark:block" />
            </flux:button>
        </flux:tooltip>

        <flux:dropdown position="bottom" align="end">
            <flux:profile :name="$usuario?->nombre_usu" :initials="$usuario?->iniciales()" aria-label="Menú de la cuenta" />

            <flux:menu class="min-w-60">
                <div class="px-2 py-2">
                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $usuario?->nombre_usu }}</div>
                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $usuario?->usuario_usu }}</div>
                    <flux:badge size="sm" class="mt-2">{{ $usuario?->rol?->nombre_rol }}</flux:badge>
                </div>

                <flux:menu.separator />

                <form method="POST" action="{{ route('auth.salir') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="logout-03" class="w-full">
                        Cerrar sesión
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main>
        <div class="mx-auto w-full max-w-7xl">
            {{ $slot }}
        </div>
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
