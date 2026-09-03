<?php

use App\Http\Controllers\ClientVehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

// HU1 - Registro y busqueda de clientes y vehiculos
Route::get('/clientes/registro', [ClientVehicleController::class, 'create'])->name('clientes.create');
Route::post('/clientes', [ClientVehicleController::class, 'store'])->name('clientes.store');
Route::get('/clientes/buscar', [ClientVehicleController::class, 'search'])->name('clientes.buscar');
