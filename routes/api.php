<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('jwt.auth')->get('users', function(Request $request) {
    return auth()->user();
});

//jwt Register
Route::post('user/register', 'APIRegisterController@register');
//jwt login
Route::post('user/login', 'APILoginController@login');
//profile 
Route::resource('user/profile', 'UserController');
//change password
Route::post('user/changePassword', 'APIRegisterController@changePassword');
//jwt logout
Route::post('user/logout', 'APILoginController@logout');
//recover password
Route::post('user/recover', 'APIRegisterController@recover');
//update new password
Route::post('user/resetPassChange', 'APIRegisterController@resetPassChange');
//Retrieve all google Accounts
Route::resource('google', 'GoogleController');
//google Account based calendar events
Route::get('googleClients', 'gCalendarController@googleClients')->name('googleClients');
//Account based particular event
Route::post('clientsEvent/{account?}/{eventId?}', 'gCalendarController@show')->name('showClientEvent');
//send meeting details
Route::post('user/sendMeetDetails', 'UserController@sendMeetDetails');
//show Blob
Route::post('user/showBlob/{meetId}', 'UserController@showBlob');
//web
    //Route::resource('cal', 'gCalendarController');
//Retrieve All events Based on Google AcccessToken
    //Route::post('getEvents/{accessToken}', 'gCalendarController@getEvents');
//Retrieve event Based on Google AcccessToken and EventId
    //Route::get('showEvent/{accessToken}/{eventid}', 'gCalendarController@showEvent');



