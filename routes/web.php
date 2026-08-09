<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

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

Route::redirect('/', '/contacts');

Route::prefix('contacts')->name('contact.')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('index');
    Route::get('/thanks', [ContactController::class, 'thanks'])->name('thanks');

    Route::middleware('throttle:contact')->group(function () {
        Route::post('/confirm', [ContactController::class, 'confirm'])->name('confirm');
        Route::post('/', [ContactController::class, 'store'])->name('store');
    });
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/export', [AdminController::class, 'export'])->name('export');

    Route::get('/contacts/{id}', [AdminController::class, 'show'])->name('show');
    Route::delete('/contacts/{id}', [AdminController::class, 'destroy'])->name('contacts.destroy');

    Route::resource('tags', TagController::class)->only(['edit', 'store', 'update', 'destroy']);
});
