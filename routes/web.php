<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'App\Http\Controllers\HomeController@index');
Route::get('home', 'App\Http\Controllers\HomeController@index')->name('home');

Route::post('login', 'App\Http\Controllers\LoginController@login')->name('login');
Route::get('login', 'App\Http\Controllers\LoginController@index');
Route::post('logout', 'App\Http\Controllers\LoginController@logout')->name('logout');


Route::get('/editCalendar/uploadCalendar', 'App\Http\Controllers\CalendarController@showUpload');
Route::post('/editCalendar/uploadCalendar', 'App\Http\Controllers\CalendarController@upload')->name("uploadCalendar");
Route::post('/editCalendar/updateCalendar', 'App\Http\Controllers\CalendarController@update')->name("updateCalendar");
Route::get('/showCalendar/{year?}', 'App\Http\Controllers\CalendarController@index')->name('showCalendar');