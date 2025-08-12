<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/signin',[AuthController::class,'signin']);
Route::post('/signup',[AuthController::class,'signup']);
Route::post('/updateprofile',[AuthController::class,'updateprofile'])->middleware('auth:sanctum');
Route::get('/posts',[PostController::class,'index'])->middleware('auth:sanctum');
Route::get('/posts/public',[PostController::class,'index']);
Route::get('/post/{id}',[PostController::class,'show'])->middleware('auth:sanctum');
Route::post('/createpost',[PostController::class,'createpost'])->middleware('auth:sanctum');
Route::delete('/deletepost/{id}',[PostController::class,'deletepost'])->middleware('auth:sanctum');
Route::post('/signout',[AuthController::class,'signout'])->middleware('auth:sanctum');
Route::get('/myposts',[PostController::class,'myposts'])->middleware('auth:sanctum');
Route::get('/bookmarks',[PostController::class,'bookmarks'])->middleware('auth:sanctum');
Route::post('/bookmark',[PostController::class,'bookmark'])->middleware('auth:sanctum');
Route::delete('/unbookmark/{id}',[PostController::class,'unbookmark'])->middleware('auth:sanctum');
Route::post('/like',[PostController::class,'like'])->middleware('auth:sanctum');
Route::delete('/unlike/{id}',[PostController::class,'unlike'])->middleware('auth:sanctum');
Route::post('/comment',[PostController::class,'comment'])->middleware('auth:sanctum');
