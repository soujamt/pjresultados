<?php

use App\Http\Controllers\Auth\SalirController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inicio');

/*
 * No hay registro publico: las cuentas las crea el super administrador desde
 * la pantalla de usuarios.
 */
Route::middleware('guest')->group(function () {
    Route::livewire('/acceder', 'pages::auth.login')->name('auth.login');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/inicio', 'pages::inicio')->name('inicio');

    Route::name('seleccion.')->group(function (): void {
        Route::livewire('/procesos', 'pages::seleccion.procesos')->name('procesos');
        Route::livewire('/unidades', 'pages::seleccion.unidades')->name('unidades');
        Route::livewire('/puestos', 'pages::seleccion.puestos')->name('puestos');
        Route::livewire('/inscripciones', 'pages::seleccion.inscripciones')->name('inscripciones');
    });

    Route::name('evaluacion.')->group(function (): void {
        Route::livewire('/examenes', 'pages::evaluacion.examenes')->name('examenes');
        Route::livewire('/resultados', 'pages::evaluacion.resultados')->name('resultados');
    });

    Route::prefix('seguridad')->name('seguridad.')->group(function (): void {
        Route::livewire('/usuarios', 'pages::seguridad.usuarios')->name('usuarios');
        Route::livewire('/roles', 'pages::seguridad.roles')->name('roles');
    });

    Route::post('/salir', SalirController::class)->name('auth.salir');
});
