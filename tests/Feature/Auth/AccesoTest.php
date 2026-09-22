<?php

use App\Enums\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auth\AccesoService;

it('concede al super administrador todos los permisos aunque su lista este vacia', function () {
    $usuario = Usuario::factory()->superAdministrador()->create();

    foreach (Permiso::cases() as $permiso) {
        expect($usuario->can($permiso->value))->toBeTrue();
    }

    expect(app(AccesoService::class)->permisos($usuario))->toBe(Permiso::cases());
});

it('concede solo los permisos que tiene el rol', function () {
    $usuario = Usuario::factory()->create(['id_rol' => Rol::factory()->con([Permiso::PuestosVer])]);

    expect($usuario->can(Permiso::PuestosVer->value))->toBeTrue()
        ->and($usuario->can(Permiso::PuestosImportar->value))->toBeFalse();
});

it('niega todo a un usuario deshabilitado aunque sea super administrador', function () {
    $usuario = Usuario::factory()->superAdministrador()->deshabilitado()->create();

    expect(app(AccesoService::class)->puede($usuario, Permiso::UsuariosVer))->toBeFalse();
});

it('niega todo cuando el rol esta deshabilitado', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->con([Permiso::UsuariosVer])->deshabilitado(),
    ]);

    expect(app(AccesoService::class)->puede($usuario, Permiso::UsuariosVer))->toBeFalse();
});

it('refresca la cache al cambiar los permisos del rol', function () {
    $rol = Rol::factory()->create();
    $usuario = Usuario::factory()->create(['id_rol' => $rol]);
    $accesos = app(AccesoService::class);

    expect($accesos->puede($usuario, Permiso::UsuariosVer))->toBeFalse();

    $rol->update(['permisos_rol' => [Permiso::UsuariosVer->value]]);

    expect($accesos->puede($usuario->fresh(), Permiso::UsuariosVer))->toBeTrue();
});

it('descarta los permisos que ya no existen en el enum', function () {
    $usuario = Usuario::factory()->create([
        'id_rol' => Rol::factory()->create(['permisos_rol' => [Permiso::UsuariosVer->value, 'modulo.retirado']]),
    ]);

    expect(app(AccesoService::class)->permisos($usuario))->toBe([Permiso::UsuariosVer]);
});
