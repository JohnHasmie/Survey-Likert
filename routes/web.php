<?php

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
    return view('welcome');
})->middleware('guest');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::middleware('is_admin')->group(function () {
        Route::prefix('survey')->group(function () {
            Route::get('/list', App\Http\Livewire\SurveyUsers::class)->name('survey.list');
            Route::get('/manage', App\Http\Livewire\ManageSurveys::class)->name('survey.manage');
            Route::get('/user/{user}', App\Http\Livewire\ShowSurveys::class)->name('user.surveys');
            Route::get('/edit/{user}/{survey}', App\Http\Livewire\EditResponses::class)->name('edit.survey');
        });
    });
});
