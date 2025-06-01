<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\RegisterController;


//=================================== User Auth Routes =============================

Route::group(['prefix' => 'api/v1'], function () {

    Route::group(['prefix' => 'auth'], function () {

      //=================================== User Login =============================

      Route::group(['controller' => AuthController::class], function () {

      Route::post('/login-email' , 'loginEmail');

      Route::post('/login-mobile' , 'loginMobile');

      Route::post('/login-google' , 'googleLogin');

      Route::post('/login-apple' , 'appleLogin');

      Route::post('/login-otp' , 'loginOtp');

      Route::post('/verify-login-otp' , 'verifyLoginOtp');

      Route::post('/verify-token' , 'verifyToken')->middleware('user.auth');

      Route::post('/logout', 'logout')->middleware('user.auth');


     //=================================== User Forget password =============================

      Route::group(['prefix' => 'forgot-password'], function () {

        Route::post('/send-otp' , 'sendForgotPasswordOtp');

        Route::post('/verify-otp' , 'verifyForgotPasswordOtp');

        Route::post('/reset-password' , 'resetForgotPassword');

      });

      //=================================== User Reset password =============================

      Route::group(['prefix' => 'reset-password' , 'middleware' => ['user.auth']], function () {

        Route::post('/send-otp' , 'sendResetPasswordOtp');

        Route::post('/verify-otp' , 'verifyResetPasswordOtp');

        Route::post('/reset-password' , 'resetResetPassword');

      });

      //=================================== User Register =============================

      Route::group(['prefix' => 'register' , 'controller' => RegisterController::class], function () {

        Route::post('/create-account' , 'createAccount');

        Route::post('/send-otp' , 'sendOtp');

        Route::post('/verify-otp' , 'verifyOtp');

        Route::get('/get-interests' , 'getInterests');
        
        Route::post('complete-interests' , 'completeInterests');  

        Route::post('complete-gender' , 'completeGender');

        Route::post('complete-birthday' , 'completeBirthday');

        Route::post('complete-image' , 'completeImage');

        Route::post('complete-reffer' , 'completeReffer');

         });
      });

   });  

   
});

