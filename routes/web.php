<?php

use App\Http\Controllers\Accountability\ObjectiveController;
use App\Http\Controllers\Accountability\SlaRecordController;
use App\Http\Controllers\Vendor\ContractController;
use App\Http\Controllers\Vendor\VendorActionController;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\Workflow\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/vendors'));

// ── Vendors ────────────────────────────────────────────────────────────────────
Route::resource('vendors', VendorController::class)->only(['index', 'create', 'store', 'show']);

Route::prefix('vendors/{vendor}')->name('vendors.')->group(function () {
    Route::post('activate',  [VendorActionController::class, 'activate'])->name('activate');
    Route::post('suspend',   [VendorActionController::class, 'suspend'])->name('suspend');
    Route::post('reinstate', [VendorActionController::class, 'reinstate'])->name('reinstate');
    Route::post('terminate', [VendorActionController::class, 'terminate'])->name('terminate');

    Route::get('contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('contracts',       [ContractController::class, 'store'])->name('contracts.store');
});

// ── Contracts ──────────────────────────────────────────────────────────────────
Route::prefix('contracts/{contract}')->name('contracts.')->group(function () {
    Route::get('',           [ContractController::class, 'show'])->name('show');
    Route::post('activate',  [ContractController::class, 'activate'])->name('activate');
    Route::post('terminate', [ContractController::class, 'terminate'])->name('terminate');
});

// ── Workflows ──────────────────────────────────────────────────────────────────
Route::resource('workflows', WorkflowController::class)->only(['index', 'create', 'store', 'show']);

// ── Objectives ─────────────────────────────────────────────────────────────────
Route::resource('objectives', ObjectiveController::class)->only(['index', 'create', 'store']);
Route::post('objectives/{objective}/complete', [ObjectiveController::class, 'complete'])->name('objectives.complete');
Route::post('objectives/{objective}/miss',     [ObjectiveController::class, 'miss'])->name('objectives.miss');

// ── SLA Records ────────────────────────────────────────────────────────────────
Route::get('sla-records', [SlaRecordController::class, 'index'])->name('sla-records.index');
