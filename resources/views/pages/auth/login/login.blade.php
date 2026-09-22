<div>
    <div class="mb-8">
        <flux:heading size="xl" level="1" class="text-2xl! tracking-tight">Iniciar sesión</flux:heading>
        <flux:subheading class="mt-2">
            Ingresa con el correo asociado a tu cuenta.
        </flux:subheading>
    </div>

    <form wire:submit="autenticar" class="flex flex-col gap-5">
        <flux:input
            wire:model="form.usuario"
            type="email"
            label="Correo"
            placeholder="correo@pj.gob.pe"
            autocomplete="username"
            icon="envelope"
            autofocus
        />

        <flux:input
            wire:model="form.clave"
            type="password"
            label="Contraseña"
            placeholder="••••••••"
            autocomplete="current-password"
            icon="lock-closed"
            viewable
        />

        <flux:checkbox wire:model="form.recordarme" label="Mantener la sesión iniciada" />

        <flux:button type="submit" variant="primary" class="mt-1 w-full">Ingresar</flux:button>
    </form>

    <div class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900">
        <p class="text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
            Las cuentas de acceso las crea el administrador del sistema. Si aún no tienes una o
            perdiste el acceso, comunícate con el Comité de Selección.
        </p>
    </div>
</div>
