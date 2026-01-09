<?php

use Illuminate\Support\Facades\Route;


require __DIR__ . '/sso-auth.php';
Route::get('/', function () {
    return view('welcome');
});
