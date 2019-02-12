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

Route::group(['prefix' => 'twitch'], function()
{
    $strNameRegex = '([$A-z0-9@]{1,50})';

    Route::get('/', function() { echo 'Twitch'; });

    Route::get('followage/{channel?}/{user?}', 'TwitchController@followAge')
        ->where('channel', $strNameRegex)
        ->where('user', $strNameRegex);
});