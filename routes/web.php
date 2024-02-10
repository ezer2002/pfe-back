<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;
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

//Route::get('/', [AiController::class,'index']);

Route::view('/', 'profile_generators.create');

//Route::post('/generate-profile', [AiController::class,'index']);

