<?php

use App\Http\Controllers\JsonPlaceHolderController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use App\Libraries\MyHelper;


Route::get('/', function () {
    dd(app()->getBindings());
    return view('welcome');
});

Route::get('/test', function(){
    $helper = new MyHelper();
    return $helper->doSomething();
});


Route::get('test2',[TestController::class, 'testDependencies']);

Route::get('users', [JsonPlaceHolderController::class, 'getPosts']);


