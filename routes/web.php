<?php

use App\Http\Controllers\ClientVehicleController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

// HU1 - Registro y busqueda de clientes y vehiculos
Route::get('/clientes/registro', [ClientVehicleController::class, 'create'])->name('clientes.create');
Route::post('/clientes', [ClientVehicleController::class, 'store'])->name('clientes.store');
Route::get('/clientes/buscar', [ClientVehicleController::class, 'search'])->name('clientes.buscar');

// HU2 - Ordenes de trabajo
Route::get('/ordenes-trabajo', [WorkOrderController::class, 'index'])->name('ordenes.index');
Route::get('/ordenes-trabajo/abrir', [WorkOrderController::class, 'create'])->name('ordenes.create');
Route::post('/ordenes-trabajo', [WorkOrderController::class, 'store'])->name('ordenes.store');
Route::get('/ordenes-trabajo/{workOrder}', [WorkOrderController::class, 'show'])->name('ordenes.show');
Route::patch('/ordenes-trabajo/{workOrder}', [WorkOrderController::class, 'update'])->name('ordenes.update');
Route::patch('/ordenes-trabajo/{workOrder}/estado', [WorkOrderController::class, 'updateStatus'])->name('ordenes.status');
