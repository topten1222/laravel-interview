<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('login', function(){
    abort('401');
})->name('login');

Route::prefix('admin')->group(function (){
    Route::post('/register', 'ApiAuthController@register');
    Route::post('/login', 'ApiAuthController@login');
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/me', 'ApiAuthController@me');
    Route::get('/product', 'ApiProductController@index');
    Route::post('/product', 'ApiProductController@create');
    Route::put('/product/{id}', 'ApiProductController@update')->where(['id' => '[0-9]+']);
});

Route::get('/login/{provider}', 'ApiProviderController@redirectToProvider');
Route::get('/login/{provider}/callback', 'ApiProviderController@handleProviderCallback');
Route::get('/product/list', 'ApiProductController@lists');
Route::post('/order/create', 'ApiOrderController@create');
