<?php

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
    return view('welcome');
});

Route::get('in/{teamId?}', 'GetStaffIn');
Route::any('slash/isin/{teamId?}', 'SlashIsIn');

Route::get('oauth/redirect', 'OAuthController@redirect')->name('oauth.redirect');
