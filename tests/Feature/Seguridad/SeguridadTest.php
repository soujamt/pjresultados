<?php

use App\Enums\EstadoRegistro;
use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Seguridad\RolService;
use App\Services\Seguridad\UsuarioService;
use Livewire\Livewire;

it('crea un usuario desde la pantalla', function () {
    $admin = Usuario::factory()->superAdministrador()->create();
    $rol = Rol::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::seguridad.usuarios')
        ->call('nuevo')
        ->set('form.nombre', 'Luis Pérez')
        ->set('form.usuario', 'LPEREZ@pj.gob.pe')
        ->set('form.rol', $rol->id_rol)
        ->set('form.clave', 'secreta123')
        ->set('form.clave_confirmation', 'secreta123')
        ->call('guardar')
        ->assertHasNoErrors();

    $usuario = Usuario::where('usuario_usu', 'lperez@pj.gob.pe')->sole();

    expect($usuario->id_rol)->toBe($rol->id_rol)
        ->and(Hash::check('secreta123', $usuario->clave_usu))->toBeTrue();
});

it('conserva la contrasena si se edita sin escribir una nueva', function () {
    $admin = Usuario::factory()->superAdministrador()->create();
    $usuario = Usuario::factory()->create(['clave_usu' => 'original123']);

    Livewire::actingAs($admin)
        ->test('pages::seguridad.usuarios')
        ->call('editar', $usuario->id_usu)
        ->set('form.nombre', 'Nombre cambiado')
        ->call('guardar')
        ->assertHasNoErrors();

    expect($usuario->fresh()->nombre_usu)->toBe('Nombre cambiado')
        ->and(Hash::check('original123', $usuario->fresh()->clave_usu))->toBeTrue();
});

it('no deja que un usuario se deshabilite o elimine a si mismo', function () {
    $admin = Usuario::factory()->superAdministrador()->create();
    $servicio = app(UsuarioService::class);

    expect(fn () => $servicio->alternarEstado($admin, $admin))->toThrow(RuntimeException::class)
        ->and(fn () => $servicio->eliminar($admin, $admin))->toThrow(RuntimeException::class);
});

it('no deja al sistema sin super administradores activos', function () {
    $admin = Usuario::factory()->superAdministrador()->create();
    $otroRol = Rol::factory()->create();
    $servicio = app(UsuarioService::class);
    $actor = Usuario::factory()->create(['id_rol' => Rol::factory()->con([Permiso::UsuariosEditar])]);

    expect(fn () => $servicio->guardar([
        'id_rol' => $otroRol->id_rol,
        'nombre_usu' => $admin->nombre_usu,
        'usuario_usu' => $admin->usuario_usu,
        'estado_usu' => EstadoRegistro::Habilitado,
    ], $admin, $actor))->toThrow(RuntimeException::class, 'único super administrador');

    expect(fn () => $servicio->alternarEstado($admin, $actor))->toThrow(RuntimeException::class, 'único super administrador');

    Usuario::factory()->create(['id_rol' => $admin->id_rol]);

    $servicio->alternarEstado($admin, $actor);

    expect($admin->fresh()->estaHabilitado())->toBeFalse();
});

it('guarda solo permisos que existen en el enum', function () {
    $rol = app(RolService::class)->guardar([
        'nombre_rol' => 'Comité',
        'permisos_rol' => [Permiso::ResultadosVer->value, 'inventado.accion'],
        'estado_rol' => EstadoRegistro::Habilitado,
    ]);

    expect($rol->permisos_rol)->toBe([Permiso::ResultadosVer->value]);
});

it('protege al rol super administrador', function () {
    $rol = Rol::factory()->superAdministrador()->create();
    $servicio = app(RolService::class);

    $servicio->guardar(['nombre_rol' => 'Super Admin', 'permisos_rol' => [], 'estado_rol' => EstadoRegistro::Deshabilitado], $rol);

    expect($rol->fresh()->es_super_rol)->toBeTrue()
        ->and($rol->fresh()->estaHabilitado())->toBeTrue()
        ->and($rol->fresh()->nombre_rol)->toBe('Super Admin')
        ->and(fn () => $servicio->eliminar($rol))->toThrow(RuntimeException::class)
        ->and(fn () => $servicio->alternarEstado($rol))->toThrow(RuntimeException::class);
});

it('no elimina un rol con usuarios asignados', function () {
    $rol = Rol::factory()->create();
    Usuario::factory()->create(['id_rol' => $rol]);

    app(RolService::class)->eliminar($rol);
})->throws(RuntimeException::class, 'usuario(s) asignado(s)');

it('marca de golpe todas las acciones de un recurso', function () {
    Livewire::actingAs(Usuario::factory()->superAdministrador()->create())
        ->test('pages::seguridad.roles')
        ->call('nuevo')
        ->call('alternarRecurso', 'puestos')
        ->assertSet('form.permisos', array_column(Permiso::agrupados()['puestos'], 'value'))
        ->call('alternarRecurso', 'puestos')
        ->assertSet('form.permisos', []);
});
