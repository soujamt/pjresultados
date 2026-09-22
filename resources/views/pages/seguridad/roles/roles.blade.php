<div class="space-y-6">
    <x-pagina.encabezado
        titulo="Roles y permisos"
        bajada="Cada rol agrupa las acciones que pueden realizar sus usuarios en cada módulo."
    >
        <x-slot:acciones>
            @can(App\Enums\Permiso::RolesCrear->value)
                <flux:button wire:click="nuevo" variant="primary" icon="plus-sign">Nuevo rol</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <x-tabla.marco>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Rol</flux:table.column>
                <flux:table.column>Permisos</flux:table.column>
                <flux:table.column align="end">Usuarios</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column align="end"><span class="sr-only">Acciones</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($roles as $rol)
                    <flux:table.row :key="$rol->id_rol">
                        <flux:table.cell class="max-w-sm whitespace-normal">
                            <div class="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-white">
                                @if ($rol->es_super_rol)
                                    <flux:icon.shield-user class="size-4 text-pj-700 dark:text-pj-400" />
                                @endif
                                {{ $rol->nombre_rol }}
                            </div>
                            @if ($rol->descripcion_rol)
                                <div class="mt-0.5 text-xs text-pretty text-zinc-500">{{ $rol->descripcion_rol }}</div>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell class="text-sm">
                            @if ($rol->es_super_rol)
                                <span class="font-medium text-pj-700 dark:text-pj-400">Acceso total</span>
                            @else
                                <span class="text-zinc-700 tabular-nums dark:text-zinc-300">{{ count($rol->permisos()) }} de {{ $totalPermisos }}</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end" class="tabular-nums">{{ $rol->usuarios_count }}</flux:table.cell>

                        <flux:table.cell>
                            <x-estado.badge :estado="$rol->estado_rol" />
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-0.5">
                                @can(App\Enums\Permiso::RolesEditar->value)
                                    <x-tabla.accion
                                        wire:click="editar({{ $rol->id_rol }})"
                                        :icon="$rol->es_super_rol ? 'view' : 'pencil-edit-02'"
                                        :tooltip="$rol->es_super_rol ? 'Ver permisos' : 'Editar'"
                                    />

                                    @unless ($rol->es_super_rol)
                                        <x-tabla.accion
                                            wire:click="alternarEstado({{ $rol->id_rol }})"
                                            :icon="$rol->estaHabilitado() ? 'view-off-slash' : 'view'"
                                            :tooltip="$rol->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                        />
                                    @endunless
                                @endcan

                                @can(App\Enums\Permiso::RolesEliminar->value)
                                    @unless ($rol->es_super_rol)
                                        <x-tabla.accion
                                            wire:click="eliminar({{ $rol->id_rol }})"
                                            wire:confirm="¿Eliminar el rol {{ $rol->nombre_rol }}?"
                                            icon="delete-02"
                                            tooltip="Eliminar"
                                        />
                                    @endunless
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <x-tabla.vacia :columnas="5" mensaje="Todavía no hay roles registrados." icono="shield-user" />
                @endforelse
            </flux:table.rows>
        </flux:table>
    </x-tabla.marco>

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
                <div class="flex items-start gap-3 rounded-lg bg-pj-50 px-4 py-3 text-sm text-pj-900 dark:bg-pj-950/40 dark:text-pj-200">
                    <flux:icon.shield-user class="mt-0.5 size-5 shrink-0 text-pj-700 dark:text-pj-400" />
                    <p class="leading-relaxed text-pretty">
                        <span class="font-semibold">Super administrador.</span>
                        Este rol tiene acceso a todo el sistema, incluidas las acciones que se agreguen más adelante.
                        Sus permisos no se pueden restringir.
                    </p>
                </div>
            @endif

            <div class="space-y-3">
                <flux:label>Permisos</flux:label>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($permisosAgrupados as $recurso => $permisos)
                        <div class="rounded-lg bg-zinc-50 p-3.5 dark:bg-white/[0.03]" wire:key="recurso-{{ $recurso }}">
                            <div class="mb-2.5 flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold tracking-[0.04em] text-zinc-600 uppercase dark:text-zinc-300">
                                    {{ App\Enums\Permiso::nombreDelRecurso($recurso) }}
                                </span>

                                @unless ($form->esSuper)
                                    <flux:button size="xs" variant="ghost" wire:click="alternarRecurso('{{ $recurso }}')">
                                        Marcar todos
                                    </flux:button>
                                @endunless
                            </div>

                            <div class="space-y-2">
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
