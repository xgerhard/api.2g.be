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
    Route::get('/', function() { echo 'Twitch'; });
    Route::get('followage/{channel?}/{user?}', 'TwitchController@followAge');
});

Route::group(['prefix' => 'games'], function()
{
    Route::get('/', function() { echo 'Games'; });
    Route::get('apex/info', function() { return view('apex.info'); });
    Route::get('apex', ['uses' => 'GamesController@run', 'game' => 'apex']);
});