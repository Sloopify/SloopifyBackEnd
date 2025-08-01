<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Admin\AdminController as AdminAdminController;
use App\Http\Controllers\Admin\Admin\Role\RoleController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\Setting\SettingController;
use App\Http\Controllers\Admin\Interest\InterestController;
use App\Http\Controllers\Admin\Feeling\FellingController;
use App\Http\Controllers\Admin\Activity\ActivityController;

Route::get('/login' , [AuthController::class , 'loginPage'])->name('login.page');

Route::post('/login/check' , [AuthController::class , 'login'])->name('login');

Route::group(['middleware' => ['admin.auth']], function () {

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    //=================================== Dashboard Route =============================

    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    //=================================== Profile Routes =============================
    
    Route::group(['prefix' => 'profile', 'as' => 'profile.', 'controller' => ProfileController::class], function () {
        
        Route::get('/', 'index')->name('index');

        Route::put('/update', 'update')->name('update');

        Route::put('/update-password', 'updatePassword')->name('update.password');
    });

    //=================================== Admin Management Routes =============================

    Route::group(['prefix' => 'admin', 'as' => 'admin.', 'controller' => AdminAdminController::class], function () {

        Route::get('/index', 'index')->name('index');

        Route::post('/store', 'store')->name('store');

        Route::get('/edit/{id}', 'edit')->name('edit');

        Route::put('/update/{id}', 'update')->name('update');

        Route::delete('/delete/{id}', 'delete')->name('delete');

        Route::put('/update/password/{id}', 'updatePassword')->name('update.password');

        Route::get('/export', 'export')->name('export');

        Route::group(['prefix' => 'role' , 'as' => 'role.' , 'controller' => RoleController::class] , function () {

            Route::get('/index', 'index')->name('index');

            Route::post('/store', 'store')->name('store');

            Route::get('/edit/{id}', 'edit')->name('edit');

            Route::put('/update/{id}', 'update')->name('update');

            Route::delete('/delete/{id}', 'delete')->name('delete');

            Route::get('/export', 'export')->name('export');
        });

    });

    Route::group(['prefix' => 'settings', 'as' => 'settings.', 'controller' => SettingController::class], function () {

        Route::get('/index', 'index')->name('index');
        
        Route::post('/update', 'update')->name('update');
        
        Route::post('/toggle-value', 'toggleValue')->name('toggle.value');
        
        Route::post('/update-description', 'updateDescription')->name('update.description');
    });


    //=================================== User Management Routes =============================


    //=================================== Interest Management Routes =============================

        Route::group(['prefix' => 'interest', 'as' => 'interest.' , 'controller' => InterestController::class], function () {

            Route::get('/index', 'index')->name('index');

            Route::post('/store', 'store')->name('store');

            Route::get('/edit/{id}', 'edit')->name('edit');

            Route::put('/update/{id}', 'update')->name('update');

            Route::delete('/delete/{id}', 'delete')->name('delete');

            Route::get('/export', 'export')->name('export');
        });


        //=================================== Feeling Management Routes =============================

        Route::group(['prefix' => 'feeling', 'as' => 'feeling.' , 'controller' => FellingController::class], function () {

            Route::get('/index', 'index')->name('index');

            Route::post('/store', 'store')->name('store');

            Route::get('/edit/{id}', 'edit')->name('edit');

            Route::put('/update/{id}', 'update')->name('update');

            Route::delete('/delete/{id}', 'delete')->name('delete');

            Route::get('/export', 'export')->name('export');
            
        });

        //=================================== Activity Management Routes =============================

        Route::group(['prefix' => 'activity', 'as' => 'activity.' , 'controller' => ActivityController::class], function () {

            Route::get('/index', 'index')->name('index');

            Route::post('/store', 'store')->name('store');

            Route::get('/edit/{id}', 'edit')->name('edit');

            Route::put('/update/{id}', 'update')->name('update');

            Route::delete('/delete/{id}', 'delete')->name('delete');

            Route::get('/export', 'export')->name('export');
        });
});
