<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\GoogleMapController;

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
Route::group([
    'prefix' => 'google-maps',
], function () {
    Route::get('', [GoogleMapController::class, 'index']);
    Route::get('{service}', [GoogleMapController::class, 'service']);
    Route::get('place/photo/{photoreference?}', [GoogleMapController::class, 'photo'])->middleware(['cache:photo']);
});
