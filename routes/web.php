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

Route::get('/', 'App\Http\Controllers\HomeController@index')->name('root');
Route::get('home', 'App\Http\Controllers\HomeController@index')->name('home');

Route::post('login', 'App\Http\Controllers\LoginController@login')->name('login');
Route::get('login', 'App\Http\Controllers\LoginController@index')->name('showLogin');
Route::post('logout', 'App\Http\Controllers\LoginController@logout')->name('logout');

Route::get('/admin/editCalendar/uploadCalendar', 'App\Http\Controllers\CalendarController@showUpload')->name("showUploadCalendar");
Route::post('/admin/editCalendar/uploadCalendar', 'App\Http\Controllers\CalendarController@upload')->name("uploadCalendar");
Route::post('/admin/editCalendar/updateCalendar', 'App\Http\Controllers\CalendarController@update')->name("updateCalendar");
Route::get('/admin/showLeaveCalendar/{year}', 'App\Http\Controllers\LeaveRecordsController@adminIndex')->name('showLeaveCalendarAdmin');
Route::post('/admin/validLeaveRecord', 'App\Http\Controllers\LeaveRecordsController@validLeaveRecord')->name("validLeaveRecordAdmin");

Route::get('/showCalendar/{year}', 'App\Http\Controllers\CalendarController@index')->name('showCalendar');
Route::get('/showLeaveCalendar/{year}', 'App\Http\Controllers\LeaveRecordsController@index')->name('showLeaveCalendar');
Route::get('/editLeaveCalendar/createLeaveRecord', 'App\Http\Controllers\LeaveRecordsController@showCreateForm')->name("showCreateLeaveForm");
Route::post('/editLeaveCalendar/createLeaveRecord', 'App\Http\Controllers\LeaveRecordsController@create')->name("createLeaveRecord");
