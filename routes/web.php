<?php

use App\Http\Controllers\MonitorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::resource('monitors', MonitorController::class)->except(['create', 'edit']);
    Route::patch('monitors/{monitor}/pause', [MonitorController::class, 'pause'])->name('monitors.pause');
    Route::patch('monitors/{monitor}/resume', [MonitorController::class, 'resume'])->name('monitors.resume');
});
