<?php

namespace App\Enums;

/**
 * Acciones que un rol puede tener concedidas.
 *
 * Cada caso se registra como Gate en AppServiceProvider, de modo que en las
 * vistas y componentes se comprueba con `@can('usuarios.crear')` o
 * `$this->authorize(Permiso::UsuariosCrear->value)`.
 *
 * El valor sigue el formato `recurso.accion` para que agrupar por modulo sea
 * cuestion de mirar el prefijo. El rol super administrador no necesita tener
 * la lista completa guardada: pasa todas las comprobaciones, incluidas las de
 * permisos que se agreguen mas adelante.
 */
enum Permiso: string
{
    case UsuariosVer = 'usuarios.ver';
    case UsuariosCrear = 'usuarios.crear';
    case UsuariosEditar = 'usuarios.editar';
    case UsuariosEliminar = 'usuarios.eliminar';

    case RolesVer = 'roles.ver';
    case RolesCrear = 'roles.crear';
    case RolesEditar = 'roles.editar';
    case RolesEliminar = 'roles.eliminar';

    case ProcesosVer = 'procesos.ver';
    case ProcesosCrear = 'procesos.crear';
    case ProcesosEditar = 'procesos.editar';
    case ProcesosEliminar = 'procesos.eliminar';

    case UnidadesVer = 'unidades.ver';
    case UnidadesCrear = 'unidades.crear';
    case UnidadesEditar = 'unidades.editar';
    case UnidadesEliminar = 'unidades.eliminar';

    case PuestosVer = 'puestos.ver';
    case PuestosCrear = 'puestos.crear';
    case PuestosEditar = 'puestos.editar';
    case PuestosEliminar = 'puestos.eliminar';
    case PuestosImportar = 'puestos.importar';

    case InscripcionesVer = 'inscripciones.ver';
    case InscripcionesImportar = 'inscripciones.importar';
    case InscripcionesExportar = 'inscripciones.exportar';
    case InscripcionesEliminar = 'inscripciones.eliminar';

    case ExamenesVer = 'examenes.ver';
    case ExamenesImportar = 'examenes.importar';
    case ExamenesEliminar = 'examenes.eliminar';

    case ResultadosVer = 'resultados.ver';
    case ResultadosGenerar = 'resultados.generar';
    case ResultadosExportar = 'resultados.exportar';

    public function etiqueta(): string
    {
        return match ($this) {
            self::UsuariosVer => 'Ver usuarios',
            self::UsuariosCrear => 'Crear usuarios',
            self::UsuariosEditar => 'Editar usuarios',
            self::UsuariosEliminar => 'Eliminar usuarios',
            self::RolesVer => 'Ver roles',
            self::RolesCrear => 'Crear roles',
            self::RolesEditar => 'Editar roles y sus permisos',
            self::RolesEliminar => 'Eliminar roles',
            self::ProcesosVer => 'Ver procesos de selección',
            self::ProcesosCrear => 'Crear procesos de selección',
            self::ProcesosEditar => 'Editar procesos de selección',
            self::ProcesosEliminar => 'Eliminar procesos de selección',
            self::UnidadesVer => 'Ver unidades de organización',
            self::UnidadesCrear => 'Crear unidades de organización',
            self::UnidadesEditar => 'Editar unidades de organización',
            self::UnidadesEliminar => 'Eliminar unidades de organización',
            self::PuestosVer => 'Ver puestos',
            self::PuestosCrear => 'Crear puestos',
            self::PuestosEditar => 'Editar puestos',
            self::PuestosEliminar => 'Eliminar puestos',
            self::PuestosImportar => 'Importar puestos desde Excel',
            self::InscripcionesVer => 'Ver inscripciones',
            self::InscripcionesImportar => 'Importar inscripciones desde Excel',
            self::InscripcionesExportar => 'Exportar inscripciones a Excel',
            self::InscripcionesEliminar => 'Eliminar inscripciones',
            self::ExamenesVer => 'Ver exámenes',
            self::ExamenesImportar => 'Importar exámenes de la lectora óptica',
            self::ExamenesEliminar => 'Vaciar los exámenes cargados',
            self::ResultadosVer => 'Ver resultados',
            self::ResultadosGenerar => 'Generar resultados',
            self::ResultadosExportar => 'Exportar resultados',
        };
    }

    /**
     * Recurso al que pertenece la accion, para agrupar la matriz de permisos
     * en la pantalla de roles.
     */
    public function recurso(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Nombre visible de un recurso en la matriz de permisos.
     */
    public static function nombreDelRecurso(string $recurso): string
    {
        return match ($recurso) {
            'usuarios' => 'Usuarios',
            'roles' => 'Roles y permisos',
            'procesos' => 'Procesos de selección',
            'unidades' => 'Unidades de organización',
            'puestos' => 'Puestos',
            'inscripciones' => 'Inscripciones',
            'examenes' => 'Exámenes',
            'resultados' => 'Resultados',
            default => ucfirst($recurso),
        };
    }

    /**
     * Todos los permisos agrupados por recurso.
     *
     * @return array<string, list<self>>
     */
    public static function agrupados(): array
    {
        $agrupados = [];

        foreach (self::cases() as $permiso) {
            $agrupados[$permiso->recurso()][] = $permiso;
        }

        return $agrupados;
    }

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
