<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


// Define your API routes here
Route::prefix('api')->group(function () {
});
