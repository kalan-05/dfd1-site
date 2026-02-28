<?php

use App\Http\Controllers\Api\BlocksController;
use App\Http\Controllers\Api\DoctorsController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\ReviewsController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/blocks', [BlocksController::class, 'index']);
Route::get('/doctors', [DoctorsController::class, 'index']);
Route::get('/services', [ServicesController::class, 'index']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/reviews', [ReviewsController::class, 'index']);
Route::post('/reviews', [ReviewsController::class, 'store'])->middleware('throttle:reviews-form');
