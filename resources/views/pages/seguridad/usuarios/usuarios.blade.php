<div class="space-y-6">
    <x-pagina.encabezado titulo="Usuarios" bajada="Cuentas con acceso al sistema y el rol que define lo que pueden hacer.">
        <x-slot:acciones>
            @can(App\Enums\Permiso::UsuariosCrear->value)
                <flux:button wire:click="nuevo" variant="primary" icon="plus">Nuevo usuario</flux:button>
            @endcan
        </x-slot:acciones>
    </x-pagina.encabezado>

    <div class="w-full sm:w-72">
        <flux:input wire:model.live.debounce.300ms="busqueda" icon="magnifying-glass" placeholder="Buscar por nombre o correo" clearable />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Usuario</flux:table.column>
            <flux:table.column>Rol</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column align="end">Acciones</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($usuarios as $usuario)
                <flux:table.row :key="$usuario->id_usu">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :initials="$usuario->iniciales()" />
                            <div>
                                <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $usuario->nombre_usu }}
                                    @if ($usuario->is(auth()->user()))
                                        <span class="text-xs font-normal text-zinc-500">(tú)</span>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500">{{ $usuario->usuario_usu }}</div>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$usuario->rol?->es_super_rol ? 'red' : 'zinc'">
                            {{ $usuario->rol?->nombre_rol ?? 'Sin rol' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <x-estado.badge :estado="$usuario->estado_usu" />
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-1">
                            @can(App\Enums\Permiso::UsuariosEditar->value)
                                <x-tabla.accion wire:click="editar({{ $usuario->id_usu }})" icon="pencil-square" tooltip="Editar" />

                                @unless ($usuario->is(auth()->user()))
                                    <x-tabla.accion
                                        wire:click="alternarEstado({{ $usuario->id_usu }})"
                                        :icon="$usuario->estaHabilitado() ? 'eye-slash' : 'eye'"
                                        :tooltip="$usuario->estaHabilitado() ? 'Deshabilitar' : 'Habilitar'"
                                    />
                                @endunless
                            @endcan

                            @can(App\Enums\Permiso::UsuariosEliminar->value)
                                @unless ($usuario->is(auth()->user()))
                                    <x-tabla.accion
                                        wire:click="eliminar({{ $usuario->id_usu }})"
                                        wire:confirm="¿Eliminar la cuenta de {{ $usuario->nombre_usu }}?"
                                        icon="trash"
                                        tooltip="Eliminar"
                                    />
                                @endunless
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <x-tabla.vacia :columnas="4" mensaje="Ningún usuario coincide con la búsqueda." icono="users" />
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="usuario" class="w-full md:max-w-lg">
        <form wire:submit="guardar" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->id ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>
                <flux:subheading>El correo es el usuario con el que se inicia sesión.</flux:subheading>
            </div>

            <flux:input wire:model="form.nombre" label="Nombre completo" />

            <flux:input wire:model="form.usuario" type="email" label="Correo" placeholder="correo@pj.gob.pe" autocomplete="off" />

            <flux:select wire:model="form.rol" label="Rol" placeholder="Elige un rol">
                @foreach ($roles as $rol)
                    <flux:select.option :value="$rol->id_rol">{{ $rol->nombre_rol }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    wire:model="form.clave"
                    type="password"
                    label="Contraseña"
                    :description="$form->id ? 'Déjala vacía para no cambiarla.' : null"
                    autocomplete="new-password"
                    viewable
                />
                <flux:input wire:model="form.clave_confirmation" type="password" label="Confirmar contraseña" autocomplete="new-password" viewable />
            </div>

            <flux:select wire:model="form.estado" label="Estado">
                @foreach ($estados as $estado)
                    <flux:select.option :value="$estado->value">{{ $estado->etiqueta() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
