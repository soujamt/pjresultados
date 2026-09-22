<?php

namespace App\Providers;

use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auth\AccesoService;
use Carbon\Translator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccesoService::class);
    }

    public function boot(): void
    {
        $this->registrarGates();
        $this->invalidarCacheDeAccesos();
        $this->nombrarLosMesesComoEnElPeru();
    }

    /**
     * Carbon traduce el noveno mes como «septiembre», pero los documentos
     * oficiales en el Peru escriben «setiembre». Se corrige el locale entero en
     * vez de cada vista, para que valga en cualquier fecha con el mes en letras.
     */
    private function nombrarLosMesesComoEnElPeru(): void
    {
        $traductor = Carbon::getTranslator();

        if (! $traductor instanceof Translator) {
            return;
        }

        $traductor->setMessages('es', [
            'months' => ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'],
            'months_short' => ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'set.', 'oct.', 'nov.', 'dic.'],
        ]);
    }

    /**
     * Cada caso del enum Permiso se publica como Gate, de modo que en las
     * vistas baste con `@can('usuarios.crear')`. El super administrador pasa
     * por AccesoService igual que el resto: ahi se resuelve que lo puede todo.
     */
    private function registrarGates(): void
    {
        foreach (Permiso::cases() as $permiso) {
            Gate::define(
                $permiso->value,
                fn (Usuario $usuario) => app(AccesoService::class)->puede($usuario, $permiso)
            );
        }
    }

    /**
     * Los permisos se cachean por rol. Si se editan desde la pantalla de roles
     * y no se limpia la cache, el usuario sigue viendo el menu anterior hasta
     * que expire —y como se guarda para siempre, eso no pasa nunca.
     */
    private function invalidarCacheDeAccesos(): void
    {
        $olvidar = fn (Rol $rol) => app(AccesoService::class)->olvidar($rol->id_rol);

        Rol::saved($olvidar);
        Rol::deleted($olvidar);
    }
}
