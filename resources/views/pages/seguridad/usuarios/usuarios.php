<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Livewire\Forms\UsuarioForm;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Seguridad\UsuarioService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Title('Usuarios | Resultados PJ')]
class extends Component
{
    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    public UsuarioForm $form;

    public function mount(): void
    {
        $this->authorize(Permiso::UsuariosVer->value);
    }

    public function nuevo(): void
    {
        $this->authorize(Permiso::UsuariosCrear->value);

        $this->form->reset();
        $this->resetValidation();

        Flux::modal('usuario')->show();
    }

    public function editar(int $id): void
    {
        $this->authorize(Permiso::UsuariosEditar->value);

        $this->form->llenar(Usuario::findOrFail($id));
        $this->resetValidation();

        Flux::modal('usuario')->show();
    }

    public function guardar(UsuarioService $servicio): void
    {
        $this->authorize($this->form->id === null
            ? Permiso::UsuariosCrear->value
            : Permiso::UsuariosEditar->value);

        $this->form->validate();

        $usuario = $this->form->id === null ? null : Usuario::findOrFail($this->form->id);

        try {
            $servicio->guardar($this->form->datos(), $usuario, auth()->user());
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::modal('usuario')->close();
        $this->form->reset();

        Flux::toast(text: 'El usuario fue guardado.', variant: 'success');
    }

    public function alternarEstado(int $id, UsuarioService $servicio): void
    {
        $this->authorize(Permiso::UsuariosEditar->value);

        try {
            $servicio->alternarEstado(Usuario::findOrFail($id), auth()->user());
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El estado fue actualizado.', variant: 'success');
    }

    public function eliminar(int $id, UsuarioService $servicio): void
    {
        $this->authorize(Permiso::UsuariosEliminar->value);

        try {
            $servicio->eliminar(Usuario::findOrFail($id), auth()->user());
        } catch (RuntimeException $error) {
            Flux::toast(text: $error->getMessage(), variant: 'danger', duration: 6000);

            return;
        }

        Flux::toast(text: 'El usuario fue eliminado.', variant: 'success');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $busqueda = trim($this->busqueda);

        return [
            'usuarios' => Usuario::query()
                ->with('rol')
                ->when($busqueda !== '', fn ($consulta) => $consulta->where(function ($consulta) use ($busqueda): void {
                    $consulta->where('nombre_usu', 'like', "%{$busqueda}%")
                        ->orWhere('usuario_usu', 'like', "%{$busqueda}%");
                }))
                ->orderBy('nombre_usu')
                ->get(),
            'roles' => Rol::query()->orderByDesc('es_super_rol')->orderBy('nombre_rol')->get(),
            'estados' => EstadoRegistro::cases(),
        ];
    }
};
