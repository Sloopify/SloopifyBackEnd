<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\AuthController;


//=================================== User Auth Routes =============================

 Route::post('/login-email' , [AuthController::class , 'loginEmail']);

 Route::post('/login-mobile' , [AuthController::class , 'loginMobile']);

 Route::group(['middleware' => ['user.auth']], function () {

    Route::get('/logout', [AuthController::class, 'logout']);

});

