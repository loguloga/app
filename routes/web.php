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

Auth::routes();

Route::get('googleClients', 'gCalendarController@googleClients')->name('googleClients');
Route::get('user/verify/{verification_code}', 'APIRegisterController@verifyUser');

//Route::resource('gcalendar', 'gCalendarController');
// Route::get('oauth', ['as' => 'oauthCallback', 'uses' => 'gCalendarController@oauth']);
//Route::get('getDetails/{accesstoken}', 'gCalendarController@getDetails');
//Route::get('oauth/{oauth}', 'gCalendarController@oauthCopy')->name('oauthCallbacks');
//Route::get('getclients/{getclients}/{account?}', 'gCalendarController@getClients')->name('getclients');
//Route::get('account/', 'gCalendarController@getClient')->name('account');
//Route::get('/payment/make', 'PaymentsController@make')->name('payment.make');


