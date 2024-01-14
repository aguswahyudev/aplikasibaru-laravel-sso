<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::controller(SsoController::class)->group(function () {
        Route::get('redirect', 'getLogin')->name('redirect');
        Route::get('callback', 'getCallback')->name('callback');
        Route::get('connect', 'getConnect')->name('connect');
        Route::get('login', 'index')->name('login');
    });
});


Route::middleware('auth')->group(function () {
    Route::get('logout', [SsoController::class, 'logout'])->name('logout');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
