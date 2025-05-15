<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\admin\Admin\AdminController as AdminAdminController;
use App\Http\Controllers\admin\Admin\Role\RoleController;
use App\Http\Controllers\Admin\ProfileController;


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

});
