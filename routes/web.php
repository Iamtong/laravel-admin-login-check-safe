<?php

use Encore\LoginCheckSafe\Http\Controllers\LoginCheckSafeController;
use \Encore\LoginCheckSafe\Http\Controllers\UserController;
use Encore\LoginCheckSafe\Http\Controllers\LogController;

Route::get('auth/login', LoginCheckSafeController::class.'@login');
Route::post('auth/login', LoginCheckSafeController::class.'@postLogin');
Route::get('auth/setting', LoginCheckSafeController::class.'@getSetting')->name('admin.setting');
Route::put('auth/setting', LoginCheckSafeController::class.'@putSetting');
Route::resource('auth/users', UserController::class);
Route::resource('auth/logs', LogController::class);
Route::resource('auth/loginlogs', Encore\LoginCheckSafe\Http\Controllers\LogLoginController::class);
Route::resource('auth/passlogs', Encore\LoginCheckSafe\Http\Controllers\LogPassController::class);
