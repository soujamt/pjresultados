/**
 * Cambia entre modo claro y oscuro sin que la página «se derrita».
 *
 * Al cambiar de tema casi todos los elementos cambian de color a la vez, y
 * los que tienen transición la animan juntos. Se apagan las transiciones un
 * instante, se aplica el cambio, se fuerza el recálculo de estilos y se
 * devuelven en el siguiente cuadro.
 *
 * Uso con Alpine: x-on:click="cambiarSinTransiciones(() => $flux.dark = !$flux.dark)"
 */
window.cambiarSinTransiciones = (cambio) => {
    const estilo = document.createElement('style');
    estilo.append(document.createTextNode('*,*::before,*::after{transition:none !important}'));
    document.head.append(estilo);

    cambio();

    void document.body.offsetHeight;

    requestAnimationFrame(() => {
        requestAnimationFrame(() => estilo.remove());
    });
};
