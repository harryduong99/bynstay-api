<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\Admin\HostController;
use App\Http\Controllers\API\Admin\ClientController;
use App\Http\Controllers\API\Admin\HomestayController;
use App\Http\Controllers\API\Admin\HomeStayTypeController;
use App\Http\Controllers\API\Admin\HomestayPolicyController;
use App\Http\Controllers\API\Admin\HomestayUtilityController;
use App\Http\Controllers\API\Admin\HomestayPolicyTypeController;
use App\Http\Controllers\API\Common\HSImageController;
use App\Http\Controllers\API\Common\HSUtilityController;
use App\Http\Controllers\API\Common\HSPriceController;
use App\Http\Controllers\API\Common\LocationController;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'users']);

    Route::prefix('admin')->group(function () {
        Route::resource('ad-homestay', HomestayController::class);
        Route::resource('ad-homestay-type', HomeStayTypeController::class);

        Route::resource('homestay-utility-type', HomestayUtilityController::class);

        Route::get('homestay-utility-type-parents', [HomestayUtilityController::class, 'getParents']);
        Route::get('homestay-utility-type/get-list-child/{id}', [HomestayUtilityController::class, 'getListChildbyId']);

        Route::resource('clients', ClientController::class);
        Route::resource('hosts', HostController::class);
        Route::resource('homestay-policy-type', HomestayPolicyTypeController::class);
    });

    Route::prefix('common')->group(function () {
        Route::resource('homestay', HomestayController::class);
        Route::resource('homestay-type', HomeStayTypeController::class);
        Route::resource('homestay-policy', HomestayPolicyController::class);
        Route::get('homestay-policy-full/{id}', [HomestayPolicyController::class, 'getFull']);

        Route::resource('homestay-utility', HSUtilityController::class);
        Route::get('hs-util/{id}', [HSUtilityController::class, 'getHsUtil']);
        Route::get('homestay-utility-parent', [HSUtilityController::class, 'getUtilityParent']);
        Route::get('homestay-utility-parent/{id}', [HSUtilityController::class, 'getUtilityChildByParent']);
        Route::get('homestay-utility-children', [HSUtilityController::class, 'getUtilityChild']);

        Route::resource('homestay-price', HSPriceController::class);
        Route::get('get-homestay-price/{hs_id}', [HSPriceController::class, 'getHsPrice']);
        Route::put('update-homestay-price/{id}', [HSPriceController::class, 'updateByHomestayId']);
        Route::resource('homestay-image', HSImageController::class);

        Route::prefix('location')->group(function () {
            Route::get('district', [LocationController::class, 'getDistrict']);
            Route::get('district/{id}', [LocationController::class, 'getWardByDistrict']);
            Route::get('province', [LocationController::class, 'getProvince']);
            Route::get('province/{id}', [LocationController::class, 'getDistrictByProvince']);
            Route::get('ward', [LocationController::class, 'getWard']);
        });
    });



});
