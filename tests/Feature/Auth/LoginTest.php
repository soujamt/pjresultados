<?php

use App\Models\Usuario;
use Livewire\Livewire;

it('muestra la pantalla de acceso a los visitantes', function () {
    $this->get(route('auth.login'))
        ->assertOk()
        ->assertSee('Iniciar sesión')
        ->assertSee('img/pj-logo.png')
        ->assertSee('img/pj-logo-oscuro.png')
        ->assertSee('apple-touch-icon.png');
});

it('envia al inicio a quien ya inicio sesion', function () {
    $this->actingAs(Usuario::factory()->create())
        ->get(route('auth.login'))
        ->assertRedirect(route('inicio'));
});

it('inicia sesion con credenciales correctas', function () {
    $usuario = Usuario::factory()->superAdministrador()->create([
        'usuario_usu' => 'admin@pj.gob.pe',
        'clave_usu' => 'clave-correcta',
    ]);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'ADMIN@pj.gob.pe')
        ->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasNoErrors()
        ->assertRedirect(route('inicio'));

    expect(auth()->id())->toBe($usuario->id_usu);
});

it('rechaza una contrasena incorrecta', function () {
    Usuario::factory()->create(['usuario_usu' => 'admin@pj.gob.pe', 'clave_usu' => 'clave-correcta']);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'admin@pj.gob.pe')
        ->set('form.clave', 'otra-clave')
        ->call('autenticar')
        ->assertHasErrors('form.usuario');

    expect(auth()->check())->toBeFalse();
});

it('no deja entrar a un usuario deshabilitado', function () {
    Usuario::factory()->deshabilitado()->create(['usuario_usu' => 'baja@pj.gob.pe', 'clave_usu' => 'clave-correcta']);

    Livewire::test('pages::auth.login')
        ->set('form.usuario', 'baja@pj.gob.pe')
        ->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasErrors('form.usuario');

    expect(auth()->check())->toBeFalse();
});

it('bloquea tras cinco intentos fallidos', function () {
    Usuario::factory()->create(['usuario_usu' => 'admin@pj.gob.pe', 'clave_usu' => 'clave-correcta']);

    $componente = Livewire::test('pages::auth.login')
        ->set('form.usuario', 'admin@pj.gob.pe')
        ->set('form.clave', 'otra-clave');

    foreach (range(1, 5) as $intento) {
        $componente->call('autenticar');
    }

    $componente->set('form.clave', 'clave-correcta')
        ->call('autenticar')
        ->assertHasErrors('form.usuario');

    expect(auth()->check())->toBeFalse();
});

it('cierra la sesion y regresa al acceso', function () {
    $this->actingAs(Usuario::factory()->create())
        ->post(route('auth.salir'))
        ->assertRedirect(route('auth.login'));

    expect(auth()->check())->toBeFalse();
});

it('exige sesion iniciada', function () {
    $this->get(route('inicio'))->assertRedirect(route('auth.login'));
    $this->get(route('seleccion.puestos'))->assertRedirect(route('auth.login'));
});
