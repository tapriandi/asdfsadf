<?php

use App\Http\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;


Route::get('/', [HistoryController::class, 'index'])->name('home');
Route::get('/coin/{coin}', [HistoryController::class, 'show'])->name('coin.show');
