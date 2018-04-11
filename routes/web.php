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


Route::get('/test/get_movie', 'TestController@getMovie');

Route::get('/test/get_env', 'TestController@test_get_env');

Route::get('/test/query', 'TestController@test_query');

Route::get('/test/split', 'TestController@test_split_th');

Route::get('/test/read_file', 'TestController@test_read_file');


Route::get('/bot/chat', 'BotController@chat');

Route::post('/bot/ask', 'BotController@ask');

#Route::get('/bot/ask/{text}', 'BotController@testHandleMessage');



