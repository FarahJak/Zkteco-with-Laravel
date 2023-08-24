<?php

use App\Http\Controllers\ZktecoController;
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

Route::get('get_attendance', [ZktecoController::class, 'index']);
Route::post('calculate_attendance_time', [ZktecoController::class, 'calculateAttendanceTime']);
