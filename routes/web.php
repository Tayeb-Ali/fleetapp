<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', function (string $locale) {
    $locale = in_array($locale, ['ar', 'en'], true) ? $locale : config('app.fallback_locale', 'en');
    session(['locale' => $locale]);

    return redirect()->back(fallback: '/');
})->name('lang.switch');

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/', fn () => view('dashboard'))->name('dashboard');

    Route::get('/vehicles', fn () => view('vehicles.index'))->name('vehicles.index');
    Route::get('/vehicles/create', fn () => view('vehicles.create'))->name('vehicles.create');
    Route::get('/vehicles/{vin}', fn (string $vin) => view('vehicles.show', ['vin' => $vin]))
        ->where('vin', '.*')
        ->name('vehicles.show');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{key}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{key}/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/documents/sale-order/{vin}', [DocumentController::class, 'saleOrder'])
        ->where('vin', '.*')
        ->name('documents.sale-order');
    Route::get('/documents/delivery-note/{vin}', [DocumentController::class, 'deliveryNote'])
        ->where('vin', '.*')
        ->name('documents.delivery-note');

    Route::get('/notifications', fn () => view('notifications.index'))->name('notifications.index');
    Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
        ->name('attachments.download');
    Route::get('/users', fn () => view('users.index'))->name('users.index');
    Route::get('/audit', fn () => view('audit.index'))->name('audit.index');
    Route::get('/settings', fn () => view('settings.index'))->name('settings.index');
});
