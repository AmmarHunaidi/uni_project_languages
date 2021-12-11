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


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->post('/product/create', 'ProductController@createNewProduct');
Route::middleware('auth:sanctum')->delete('product/{id}','ProductController@deleteOneProduct');
Route::middleware('auth:sanctum')->patch('product/{id}','ProductController@updateOneProduct');
Route::middleware('auth:sanctum')->get('/products/{id}','ProductController@getMyProducts');
Route::middleware('auth:sanctum')->get('/product','ProductController@getAllProducts');
Route::middleware('auth:sanctum')->get('/product/search','ProductController@searchByFilter');
Route::middleware('auth:sanctum')->get('/product/{id}','ProductController@getOneProduct');
Route::middleware('auth:sanctum')->post('/product/like/{id}','ProductController@likeProduct');
Route::middleware('auth:sanctum')->post('/product/view/{id}','ProductController@viewProduct');
Route::middleware('auth:sanctum')->post('/product/comment/{id}','ProductController@comment');
Route::middleware('auth:sanctum')->delete('/product/comment/{id}','ProductController@deletecomment');


Route::middleware('auth:sanctum')->post('logout','UserController@logout');

Route::post('/register', 'UserController@register');
Route::post('/login', 'UserController@login');

Route::get('/type','TypeController@index');
Route::post('/type','TypeController@store');
Route::patch('/type/{id}','TypeController@update');
Route::delete('/type/{id}','TypeController@destroy');
