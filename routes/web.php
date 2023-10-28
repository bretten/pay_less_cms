<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Artisan;
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

Route::get('/', function () {
    return redirect('posts');
});

Route::resource('posts', PostController::class);
Route::resource('sites', SiteController::class);
Route::post('/publish/site/{site?}', function ($site = null) {
    if ($site) {
        Artisan::call("posts:publish --site=$site");
    } else {
        Artisan::call('posts:publish');
    }

    return redirect('/' . Route::prefix(config('app.url_prefix'))->get("posts")->uri());
});
