<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\ChatGenerationController;
use App\Http\Controllers\ChatPageController;
use App\Http\Controllers\ImageGenerationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [UserAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [UserAuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [UserAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [UserAuthController::class, 'register'])->name('register.submit');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout');

    Route::get('/', ChatPageController::class)->name('home');
    Route::post('/chat/generate', ChatGenerationController::class)->name('chat.generate');
    Route::post('/image/generate', ImageGenerationController::class)->name('image.generate');
});

Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::prefix('admin')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
        Route::get('/', DashboardController::class)->name('admin.dashboard');
        Route::get('/dashboard', function () {
            return redirect()->route('admin.dashboard');
        })->name('admin.dashboard.redirect');

        Route::get('/packages', [PackageController::class, 'index'])->name('admin.packages.index');
        Route::post('/packages', [PackageController::class, 'store'])->name('admin.packages.store');
        Route::put('/packages/{package}', [PackageController::class, 'update'])->name('admin.packages.update');
        Route::patch('/packages/{package}/toggle', [PackageController::class, 'toggle'])->name('admin.packages.toggle');

        Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');

        Route::get('/purchases', [PurchaseController::class, 'index'])->name('admin.purchases.index');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('admin.purchases.store');
        Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('admin.purchases.update');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    });
});
