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

/**
 * Descarga un archivo (PDF, Excel) mostrando que se está generando.
 *
 * Con un enlace normal el navegador no avisa nada hasta que el servidor
 * termina, y el PDF de todos los puestos tarda varios segundos. Con fetch se
 * sabe cuándo empieza y cuándo termina, y si el servidor responde con un
 * error (sin permiso, faltan datos) se muestra en un aviso en vez de abrir
 * una página de error.
 *
 * Uso: <x-boton.descarga :href="..."> (resources/views/components/boton).
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('descarga', (url) => ({
        descargando: false,

        async descargar() {
            if (this.descargando) {
                return;
            }

            this.descargando = true;

            try {
                const respuesta = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!respuesta.ok) {
                    window.Flux.toast({ text: await mensajeDeError(respuesta), variant: 'danger' });

                    return;
                }

                guardarArchivo(await respuesta.blob(), nombreDelArchivo(respuesta.headers.get('Content-Disposition')));
            } catch {
                window.Flux.toast({
                    text: 'No se pudo descargar el archivo. Revisa la conexión e inténtalo otra vez.',
                    variant: 'danger',
                });
            } finally {
                this.descargando = false;
            }
        },
    }));
});

async function mensajeDeError(respuesta) {
    if (respuesta.status === 403) {
        return 'No tienes permiso para descargar este archivo.';
    }

    if (respuesta.status === 419 || respuesta.status === 401) {
        return 'Tu sesión expiró. Vuelve a ingresar para descargar el archivo.';
    }

    try {
        const { message } = await respuesta.json();

        if (message && respuesta.status < 500) {
            return message;
        }
    } catch {
        // La respuesta no era JSON: se usa el mensaje general.
    }

    return 'No se pudo generar el archivo. Inténtalo otra vez en unos minutos.';
}

/**
 * «attachment; filename=resultados.pdf» o «filename*=utf-8''resultados%20t%C3%A9cnica.pdf».
 */
function nombreDelArchivo(disposicion) {
    const codificado = disposicion?.match(/filename\*=(?:UTF-8|utf-8)''([^;]+)/);

    if (codificado) {
        return decodeURIComponent(codificado[1]);
    }

    return disposicion?.match(/filename="?([^";]+)"?/)?.[1] ?? 'descarga';
}

function guardarArchivo(contenido, nombre) {
    const direccion = URL.createObjectURL(contenido);
    const enlace = Object.assign(document.createElement('a'), { href: direccion, download: nombre });

    document.body.append(enlace);
    enlace.click();
    enlace.remove();

    setTimeout(() => URL.revokeObjectURL(direccion), 10_000);
}
