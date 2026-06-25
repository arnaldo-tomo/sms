<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Contactos & Grupos
    Route::resource('contacts', ContactController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('contacts/import', [ContactImportController::class, 'store'])->name('contacts.import');
    Route::resource('lists', ContactListController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['lists' => 'list']);

    // Envio de SMS
    Route::get('/send', [SendController::class, 'create'])->name('messages.create');
    Route::post('/send', [SendController::class, 'store'])->name('messages.store');

    // Histórico
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');

    // Dispositivos
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('/devices/sync', [DeviceController::class, 'sync'])->name('devices.sync');

    // Configurações
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test', [SettingController::class, 'testConnection'])->name('settings.test');

    // Empresas (multi-tenant / API pública)
    Route::resource('companies', CompanyController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('companies/{company}/tokens', [CompanyController::class, 'generateToken'])->name('companies.tokens.store');
    Route::delete('companies/{company}/tokens/{token}', [CompanyController::class, 'revokeToken'])->name('companies.tokens.revoke');
    Route::get('companies/{company}/usage', [CompanyController::class, 'usage'])->name('companies.usage');
    Route::post('companies/{company}/numbers', [CompanyController::class, 'assignNumber'])->name('companies.numbers.assign');
    Route::delete('companies/{company}/numbers/{device}', [CompanyController::class, 'unassignNumber'])->name('companies.numbers.unassign');

    // Gestão de utilizadores
    Route::resource('users', UserController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // Logs de auditoria
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
