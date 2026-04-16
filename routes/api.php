<?php

use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('/upload-csv', [UploadController::class, 'store'])->name('api.upload-csv');
Route::get('/records', [RecordController::class, 'index'])->name('api.records');
