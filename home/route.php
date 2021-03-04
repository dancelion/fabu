<?php
/**
 * Created by PhpStorm.
 * User: dancelion
 * Date: 2019-08-29
 * Time: 18:24
 */
use think\facade\Route;
Route::pattern([
    'shortUrl' => '\w+',
]);
Route::get('download/:shortUrl','Home/index/download');
Route::get('plist/:shortUrl','Home/index/plist');
Route::get(':shortUrl','Home/index/index');