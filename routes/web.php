<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MonitorCheckController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\MonitorHistoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');
Route::post('locale', LocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [MonitorController::class, 'index'])->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::resource('monitors', MonitorController::class)->except(['create', 'edit']);
    Route::get('monitors/{monitor}/checks', MonitorHistoryController::class)->name('monitors.checks');
    Route::post('monitors/{monitor}/check', MonitorCheckController::class)->middleware('throttle:10,1')->name('monitors.check');
    Route::patch('monitors/{monitor}/pause', [MonitorController::class, 'pause'])->name('monitors.pause');
    Route::patch('monitors/{monitor}/resume', [MonitorController::class, 'resume'])->name('monitors.resume');
});
