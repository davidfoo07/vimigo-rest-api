<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Open Routes
Route::post("register", [ApiController::class, "register"]);
Route::post("login", [ApiController::class, "login"]);

// Protected Routes
Route::group([
    "middleware" => "auth:api"
], function() {
    Route::get("search-students", [ApiController::class, "search"]);
    Route::post('import', [ApiController::class, 'import']);
    Route::post('update', [ApiController::class, 'bulkUpdate']);
    Route::post('delete', [ApiController::class, 'bulkDelete']);
    Route::get('index', [ApiController::class, 'index']);
    Route::get("logout", [ApiController::class, "logout"]);
});