<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(AuthController::class)->prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('register', 'register')->withoutMiddleware('auth:api')->name('register');
    Route::post('login', 'login')->withoutMiddleware('auth:api')->name('login');
    Route::post('refresh', 'refresh')->withoutMiddleware('auth:api')->name('refresh');
    Route::post('logout', 'logout')->name('logout');
});

Route::middleware('auth:api')->group(function () {
    Route::get('me', MeController::class)->name("me.auth");
});

Route::controller(NotesController::class)->prefix('notes')->middleware('auth:api')->group(function () {
    Route::get('/category/{category?}', 'index')->name('notes.index');
    Route::get('/show/id/{note:note_id}', 'show')->name('notes.show');
    Route::post('/create', 'store')->name('notes.create');
    Route::put('/update/{note:note_id}', 'update')->name('notes.update');
    Route::get('/menu/category', 'category')->name('notes.category');
    Route::get('/select/category', 'select')->name('notes.select');
    Route::get('/star', 'star')->name('notes.star');
    Route::patch('/star/id/{note_id}', 'starupdate')->name('notes.starupdate');
    Route::delete('/delete/id/{note_id}', 'softdestroy')->name('notes.softdestroy');
    Route::delete('/delete/permanent/id/{note_id}', 'forcedestroy')->name('notes.forcedestroy');
    Route::delete('/delete/all/permanent', 'forcedestroyall')->name('notes.forcedestroyall');
    Route::get('/delete/show', 'showdestroy')->name('notes.showdestroy');
    Route::get('/delete/restore', 'restoreall')->name('notes.restore');
    Route::get('/delete/restore/id/{note_id}', 'restoreid')->name('notes.restoreid');
    Route::post('/share/id/{note:note_id}', 'storeshare')->name('notes.storeshare');
    Route::get('/share/id/{url}', 'showshare')->name('notes.showshare');
    Route::post('/id/{share:url_generate}/duplicate', 'duplicate')->name('notes.duplicate');
});

Route::post('notes/search', SearchController::class)->middleware('auth:api')->name('notes.search');
