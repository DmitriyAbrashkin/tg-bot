<?php

use App\Http\Controllers\IpController;
use App\Http\Controllers\PhraseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/phpinfo', function () {
    phpinfo();
});

Route::get('/is-palindrome/',[PhraseController::class, 'isPalindrome'])->middleware('phrase');

Route::get('/ip/',[IpController::class, 'showIp']);
