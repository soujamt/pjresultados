<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Roles y permisos"
        bajada="Cada rol agrupa las acciones que pueden realizar sus usuarios en cada módulo."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::RolesCrear->value)
                <flux:button wire:click="nuevo" variant="primary" icon="plus">Nuevo rol</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Rol</flux:table.column>
            <flux:table.column>Permisos</flux:table.column>
            <flux:table.column align="center">Usuarios</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($roles as $rol)
                <flux:table.row :key="$rol->id_rol">
                    <flux:table.cell class="max-w-sm">
                        <div class="flex items-center gap-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $rol->nombre_rol }}
                            @if ($rol->es_super_rol)
                                <flux:icon.shield-check variant="micro" class="text-pj-600 dark:text-pj-400" />
                            @endif
                        </div>
                        @if ($rol->descripcion_rol)
                            <div class="text-xs whitespace-normal text-zinc-500">{{ $rol->descripcion_rol }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($rol->es_super_rol)
                            <flux:badge color="red" size="sm">Acceso total</flux:badge>
                        @else
                            <span class="text-sm tabular-nums">{{ count($rol->permisos()) }} de {{ $totalPermisos }}</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="center" class="tabular-nums">{{ $rol->usuarios_count }}</flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$rol->estado_rol" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::RolesEditar->value)
                                <x-tabla.accion
                                    wire:click="editar({{ $rol->id_rol }})"
                                    :icon="$rol->es_super_rol ? 'eye' : 'pencil-square'"
                                    :tooltip="$rol->es_super_rol ? 'Ver permisos' : 'Editar'"
                                />

                                @unless ($rol->es_super_rol)
                                    <x-tabla.accion
                                        wire:click="alternarEstado({{ $rol->id_rol }})"
                                        :icon="$rol->estaHabilitado() ? 'eye-slash' : 'eye'"
                                        :tooltip="$rol->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                    />
                                @endunless
                            @endcan

                            @can(App\Enums\Permiso::RolesEliminar->value)
                                @unless ($rol->es_super_rol)
                                    <x-tabla.accion
                                        wire:click="eliminar({{ $rol->id_rol }})"
                                        wire:confirm="¿Eliminar el rol {{ $rol->nombre_rol }}?"
                                        icon="trash"
                                        tooltip="Eliminar"
                                    />
                                @endunless
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="5" mensaje="Todavía no hay roles registrados." icono="shield-check" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="rol" class="w-full md:max-w-3xl">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar rol' : 'Nuevo rol' }}</flux:heading>
                <flux:subheading>Marca las acciones que podrán realizar los usuarios con este rol.</flux:subheading>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.nombre" label="Nombre" />

                <flux:select wire:model="form.estado" label="Estado" :disabled="$form->esSuper">
                    @foreach ($estados as $estado)
                        <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:input wire:model="form.descripcion" label="Descripción" />

            @if ($form->esSuper)
                <flux:callout icon="shield-check" color="red">
                    <flux:callout.heading>Super administrador</flux:callout.heading>
                    <flux:callout.text>
                        Este rol tiene acceso a todo el sistema, incluidas las acciones que se agreguen más adelante.
                        Sus permisos no se pueden restringir.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="space-y-3">
                <flux:label>Permisos</flux:label>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($permisosAgrupados as $recurso => $permisos)
                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="recurso-{{ $recurso }}">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ App\Enums\Permiso::nombreDelRecurso($recurso) }}
                                </span>

                                @unless ($form->esSuper)
                                    <flux:button size="xs" variant="ghost" wire:click="alternarRecurso('{{ $recurso }}')">
                                        Todos
                                    </flux:button>
                                @endunless
                            </div>

                            <div class="space-y-1.5">
                                @foreach ($permisos as $permiso)
                                    <flux:checkbox
                                        wire:model="form.permisos"
                                        :value="$permiso->value"
                                        :label="$permiso->etiqueta()"
                                        :disabled="$form->esSuper"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:error name="form.permisos" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
