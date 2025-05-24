<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\AuthController;


//=================================== User Auth Routes =============================

Route::group(['prefix' => 'api/v1'], function () {

    Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {

      Route::post('/login-email' , 'loginEmail');

      Route::post('/login-mobile' , 'loginMobile');

      Route::post('/login-google' , 'googleLogin');

      Route::post('/login-apple' , 'appleLogin');

      Route::post('/login-otp' , 'loginOtp');

      Route::post('/verify-login-otp' , 'verifyLoginOtp');

      Route::group(['prefix' => 'forgot-password'], function () {

        Route::post('/send-otp' , 'sendForgotPasswordOtp');

        Route::post('/verify-otp' , 'verifyForgotPasswordOtp');

        Route::post('/reset-password' , 'resetForgotPassword');

      });

      Route::group(['prefix' => 'reset-password' , 'middleware' => ['user.auth']], function () {

        Route::post('/send-otp' , 'sendResetPasswordOtp');

        Route::post('/verify-otp' , 'verifyResetPasswordOtp');

        Route::post('/reset-password' , 'resetResetPassword');

      });

    });

 Route::group(['middleware' => ['user.auth']], function () {

    Route::get('/logout', [AuthController::class, 'logout']);

});

});

