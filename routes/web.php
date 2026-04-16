<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UploadController::class, 'index'])->name('uploads.index');
Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
