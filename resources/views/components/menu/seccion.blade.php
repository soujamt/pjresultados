{{-- Título de un grupo del menú lateral. Se oculta con el menú colapsado. --}}
<div {{ $attributes->merge(['class' => 'px-3 pt-5 pb-1.5 text-[0.6875rem] leading-none font-semibold tracking-[0.06em] text-zinc-400 uppercase in-data-flux-sidebar-collapsed-desktop:hidden dark:text-zinc-500']) }}>
    {{ $slot }}
</div>
