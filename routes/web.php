<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\PostSchedulerController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\SpeechToTextController;
use App\Http\Controllers\CalendarController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/', [AiController::class,'index']);

Route::view('/', 'profile_generators.create');

Route::post('/generate-profile', [AiController::class,'index']);

Route::post('/graph-interaction',[SocialiteController::class,'handleGraphInteraction']);

Route::post('/schedule-post',[PostSchedulerController::class,'schedulePost']);

Route::post('/save-post',[DraftController::class,'saveDraft']);

Route::get('/events', [CalendarController::class, 'getEvents']);

Route::get('/meta-business', [CalendarController::class, 'fetchPostsFromMeta']);

Route::post('/transcribe-audio', [SpeechToTextController::class, 'transcribe']);