<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SettingController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::prefix('auth')->group(function () {

    //    Social Lite Routes
    // Route::get('login/{provider}', [AuthController::class, 'redirectToProvider']);
    // Route::get('login/{provider}/callback', [AuthController::class, 'handleProviderCallback']);

    //Public Routes
   
    Route::post('login', [AuthController::class, 'login']);
    Route::group(['middleware' => ['auth:api', 'role:user']], function () {
        Route::get('logout', [AuthController::class, 'logout']);
    });
});
Route::group(['middleware' => ['auth:api', 'role:user', 'check-user-status']], function () {
    
    Route::prefix('dashboard')->group(function () {
        Route::get('user-data',[DashboardController::class,'getUserData']);
        Route::get('recent-projects',[DashboardController::class,'recentProjects']);
        Route::post('user-device-token',[DashboardController::class,'userDeviceToken']);
        });
    Route::prefix('project')->group(function () {
        Route::post('info',[ProjectController::class,'info']);
        Route::post('comment',[ProjectController::class,'comment']);
        Route::post('get-comments',[ProjectController::class,'getComments']);
        Route::post('upload-images', [ProjectController::class, 'uploadImages']);
        Route::post('read-comment',[ProjectController::class,'readComment']);
    });
    Route::prefix('message')->group(function () {
        Route::post('send', [MessageController::class, 'sendMessage']);
        Route::get('get-chats',[MessageController::class,'getChats']);
        Route::get('get-messages',[MessageController::class,'getMessages']);
        Route::get('read',[MessageController::class,'read']);
    });
    Route::prefix('setting')->group(function () {
        Route::post('edit-profile', [SettingController::class, 'editProfile']);;
        Route::post('profile-image', [SettingController::class, 'profileImage']);
    });
    });       
Route::any(
    '{any}',
    function () {
        return response()->json([
            'status_code' => 404,
            'message' => 'Page Not Found. Check method type Post/Get or URL',
        ], 404);
    }
)->where('any', '.*');