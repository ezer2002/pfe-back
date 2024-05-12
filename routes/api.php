<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\PostSchedulerController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\SpeechToTextController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PageSociauxController;
use App\Http\Controllers\PostDeleteController;
use App\Http\Controllers\PostModificationController;
use Laravel\Sanctum\Sanctum;

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


/*//fetchAndSavePosts
Route::get('/-metabusiness-suite', [CalendarController::class, 'fetchMetaBusinessSuitePosts']);*/

//Route::get('/meta-business', [CalendarController::class, 'fetchScheduledPosts']);


//Route::post('/transcribe-audio', [SpeechToTextController::class, 'transcribe']);

//fetchPostsFromMeta*/

Route::post('/register',[RegisterController::class,'register']);
Route::post('/login',[LoginController::class,'login']);





Route::post('/addpagesociaux',[PageSociauxController::class,'store']);

Route::get('/getUserPages',[PageSociauxController::class,'getUserPages']);

Route::delete('/pages/{id}',[PageSociauxController::class,'destroy']);
Route::post('/generate-profile',[AiController::class,'index']);

Route::post('/graph-interaction',[SocialiteController::class,'handleGraphInteraction']);

Route::post('/schedule-post',[PostSchedulerController::class,'schedulePost']);

Route::post('/save-post',[DraftController::class,'saveDraft']);

Route::get('/events', [CalendarController::class, 'getEvents']);

Route::get('/meta-business', [CalendarController::class, 'fetchPostsFromMeta']);

Route::post('/modify-post',[PostModificationController::class,'modifyPost']);
Route::post('/delete-post',[PostDeleteController::class,'deletePost']);

