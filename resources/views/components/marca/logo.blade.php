{{--
    Logo completo con el nombre «Poder Judicial del Perú». El gris original se
    pierde sobre fondo oscuro, así que en modo oscuro se usa una variante con
    los grises aclarados; los rojos son los mismos en ambas. Las clases de
    tamaño se pasan al componente: <x-marca.logo class="h-24" />.
--}}
@props([
    'soloClaro' => false,
])

<span {{ $attributes->merge(['class' => 'inline-block']) }}>
    <img
        src="{{ asset('img/pj-logo.png') }}"
        alt="Poder Judicial del Perú"
        width="574"
        height="422"
        @class(['h-full w-auto object-contain', 'dark:hidden' => ! $soloClaro])
    />

    @unless ($soloClaro)
        <img
            src="{{ asset('img/pj-logo-oscuro.png') }}"
            alt="Poder Judicial del Perú"
            width="574"
            height="422"
            class="hidden h-full w-auto object-contain dark:block"
        />
    @endunless
</span>
