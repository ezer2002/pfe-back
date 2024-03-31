<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\PostSchedulerController;
use App\Http\Controllers\DraftController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/generate-profile',[AiController::class,'index']);

Route::post('/graph-interaction',[SocialiteController::class,'handleGraphInteraction']);

Route::post('/schedule-post',[PostSchedulerController::class,'schedulePost']);

Route::post('/save-post',[DraftController::class,'saveDraft']);