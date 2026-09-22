<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\RolForm;
use App\Models\Rol;
use App\Services\Seguridad\RolService;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Roles y permisos | Resultados PJ')]
class extends Component
{
    public RolForm $form;

    public function mount(): void
    {
        $this->authorize(Permiso::RolesVer->value);
    }

    public function nuevo(): void
    {
        $this->authorize(Permiso::RolesCrear->value);

        $this->form->reset();
        $this->resetValidation();

        Flux::modal('rol')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::RolesEditar->value);

        $this->form->llenar(Rol::findOrFail($id));
        $this->resetValidation();

        Flux::modal('rol')->show();
    }

    /**
     * Marca o desmarca todas las acciones de un recurso de la matriz.
     */
    public function alternarRecurso(string $recurso): void
    {
        $delRecurso = array_column(Permiso::agrupados()[$recurso] ?? [], 'value');
        $todos = array_diff($delRecurso, $this->form->permisos) === [];

        $this->form->permisos = $todos
            ? array_values(array_diff($this->form->permisos, $delRecurso))
            : array_values(array_unique([...$this->form->permisos, ...$delRecurso]));
    }

    public function guardar(RolService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::RolesCrear->value
            : Permiso::RolesEditar->value);

        $this->form->validate();

        $rol = $this->form->id === null ? null : Rol::findOrFail($this->form->id);
        $servicio->guardar($this->form->datos(), $rol);

        Flux::modal('rol')->close();
        $this->form->reset();

        Flux::toast(text: 'El rol fue guardado.', variant: 'success');
    }

    public function alternarEstado(int $id, RolService $servicio): void
    {
        $this->authorize(Permiso::RolesEditar->value);

        try {
            $servicio->alternarEstado(Rol::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, RolService $servicio): void
    {
        $this->authorize(Permiso::RolesEliminar->value);

        try {
            $servicio->eliminar(Rol::findOrFail($id));
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El rol fue eliminado.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'roles' => Rol::query()->withCount('usuarios')->orderByDesc('es_super_rol')->orderBy('nombre_rol')->get(),
            'permisosAgrupados' => Permiso::agrupados(),
            'totalPermisos' => count(Permiso::cases()),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
