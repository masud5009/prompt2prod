<?php

use App\Http\Controllers\ImageGenerationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('ImageGenerator');
})->name('home');

Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::post('/image/generate', ImageGenerationController::class)->name('image.generate');
