<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\AuthController;


//=================================== User Auth Routes =============================

Route::group(['prefix' => 'api/v1'], function () {

    Route::group(['controller' => AuthController::class], function () {

      Route::post('/login-email' , 'loginEmail');

      Route::post('/login-mobile' , 'loginMobile');

      Route::post('/login-google' , 'googleLogin');

      Route::post('/login-apple' , 'appleLogin');

    });

 Route::group(['middleware' => ['user.auth']], function () {

    Route::get('/logout', [AuthController::class, 'logout']);

});

});

