<?php

use App\Events\shoot;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Events\TestEvent;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/sendevent', function(){
    event(new TestEvent(['msg' => 'Hello World']));
    return response()->json(['success' => true]);
});


route::prefix('auth')->group(function(){
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/logout', [UserController::class, 'logout']);
});


route::post('/event', function(){
    event(new shoot('mensaje enviado'));
    return response()->json([
        "msg" => "mensaje enviado"
    ]);
});


