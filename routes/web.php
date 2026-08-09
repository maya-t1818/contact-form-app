<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/contacts');

// 一般向け問い合わせルーティング
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

    Route::get('/contacts/{id}', [AdminController::class, 'show'])->name('show');
    Route::delete('/contacts/{id}', [AdminController::class, 'destroy'])->name('contacts.destroy');

    Route::resource('tags', TagController::class)->only(['edit', 'store', 'update', 'destroy']);
});

Route::middleware('auth')->get('/contacts/export', [AdminController::class, 'export'])->name('contacts.export');