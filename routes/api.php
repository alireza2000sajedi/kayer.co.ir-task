<?php

use App\Http\Controllers\Admin\AdminReplyController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/* Auth */
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    /* Auth */
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    /* Ticket */
    Route::get('tickets', [TicketController::class, 'index']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('tickets', [TicketController::class, 'store']);

    /* Reply */
    Route::post('tickets/{ticket}/reply', [ReplyController::class, 'store']);
});

Route::prefix('admin')->middleware(['auth:sanctum','admin'])->group(function () {
    /* Ticket */
    Route::get('tickets', [AdminTicketController::class, 'index']);
    Route::get('tickets/{ticket}', [AdminTicketController::class, 'show']);

    /* Reply */
    Route::post('tickets/{ticket}/reply', [AdminReplyController::class, 'store']);

    /* Change Status */
    Route::post('tickets/{ticket}/change-status', [AdminTicketController::class, 'changeStatus']);
});


