<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\BackgroundController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\ConvenientController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\StatusController;
use App\Http\Controllers\API\SupportController;
use App\Http\Controllers\API\TypeRoomController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/listbg', [BackgroundController::class, 'list']);
Route::get('/listConvenient', [ConvenientController::class, 'get']);
Route::get('/listtype', [TypeRoomController::class, 'getdata']);
Route::get('/listroom', [RoomController::class, 'getRoom']);
Route::get('/getroom/{id}', [RoomController::class, 'show']);
Route::get('/sttroom', [StatusController::class, 'status_room']);
Route::get('/location', [LocationController::class, 'getLocation']);
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/available-rooms', [RoomController::class, 'getAvailableRooms']);
Route::get('/booked-dates/{roomId}', [BookingController::class, 'getBookedDates']);
Route::post('/addrequest', [SupportController::class, 'add']);
Route::get('/reviews/{id}', [CommentController::class, 'index']);

Route::get('/vnpay-return', [BookingController::class, 'vnpayReturn']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'getdata']);
    Route::post('/bookings/vnpay', [BookingController::class, 'payWithVnpay']);
    Route::post('/bookings', [BookingController::class, 'cod']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/user', [UserController::class, 'user']);
    Route::put('/user/update', [UserController::class, 'edit']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);
    Route::get('/listrequest', [SupportController::class, 'getData']);
    Route::get('/list', [SupportController::class, 'getDataByUser']);
    Route::post('/rooms/{room_id}/reviews', [CommentController::class, 'create']);
    Route::get('/reviewInBooking/{id}', [CommentController::class, 'reviewInBooking']);
    
    Route::middleware('admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'dashboard']);
        
        Route::put('/feedback/{id}', [SupportController::class, 'feedback']);

        Route::get('/admin/bookings', [BookingController::class, 'getBookings']);
        Route::put('/admin/bookings/{id}/status', [BookingController::class, 'updateStatus']);
        Route::put('/admin/bookings/{id}/payment', [BookingController::class, 'updatePaymentStatus']);
        Route::put('/admin/bookings/{id}/refund', [BookingController::class, 'updateRefund']);

        Route::post('/admin/addBackground', [BackgroundController::class, 'addBackground']);
        Route::delete('/admin/deletebg/{id}', [BackgroundController::class, 'destroy']);

        Route::post('/admin/addConvenient', [ConvenientController::class, 'create']);
        Route::delete('/admin/deleteConvenient/{id}', [ConvenientController::class, 'destroy']);

        Route::post('/admin/addtype', [TypeRoomController::class, 'create']);
        Route::delete('/admin/deletetype/{id}', [TypeRoomController::class, 'destroy']);

        Route::post('/admin/addroom', [RoomController::class, 'create']);
        Route::delete('/admin/deleteroom/{id}', [RoomController::class, 'destroy']);
        Route::put('/admin/updateroom/{id}', [RoomController::class, 'update']);

        Route::post('/location', [LocationController::class, 'updateLocation']);

        Route::get('/alluser', [UserController::class, 'getalluser']);
        Route::put('/admin/user/{id}/status', [UserController::class, 'updateStatus']);
        
        Route::post('/admin/settings', [SettingController::class, 'update']);
        Route::post('/admin/settings/delete-banner', [SettingController::class, 'deleteBanner']);
        Route::post('/admin/settings/delete-logo', [SettingController::class, 'deleteLogo']);
    });
});
