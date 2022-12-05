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

Route::get('/', function () {
    return view('home');
})->middleware('auth');

Route::post('login', 'App\Http\Controllers\LoginController@login')->name('login');
Route::get('login', function () {
    return view('login');
});
Route::post('logout', 'App\Http\Controllers\LoginController@logout')->name('logout');