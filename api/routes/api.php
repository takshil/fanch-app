<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AVoirController;
use App\Http\Controllers\Api\CalendrierController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\ReglagesController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShowController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/join', [AuthController::class, 'join']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/home', [HomeController::class, 'index']);

    Route::post('/households', [HouseholdController::class, 'store']);
    Route::post('/households/join', [HouseholdController::class, 'join']);
    Route::post('/households/{household}/switch', [HouseholdController::class, 'switch']);
    Route::delete('/households/{household}/leave', [HouseholdController::class, 'leave']);

    Route::get('/a-voir', [AVoirController::class, 'index']);
    Route::post('/episodes/{episode}/toggle', [AVoirController::class, 'toggle']);

    Route::get('/shows/{show:slug}', [ShowController::class, 'show']);
    Route::post('/shows/{show:slug}/visibility', [ShowController::class, 'setVisibility']);
    Route::post('/shows/{show:slug}/mark-all', [ShowController::class, 'markAllSeason']);
    Route::post('/shows/{show:slug}/watchlist-start', [WatchlistController::class, 'start']);
    Route::post('/shows/{show:slug}/groups', [GroupController::class, 'store']);

    Route::post('/groups/join', [GroupController::class, 'join']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::post('/groups/{group}/status', [GroupController::class, 'updateMyStatus']);
    Route::post('/groups/{group}/members', [GroupController::class, 'addFromHousehold']);
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember']);
    Route::get('/groups/{group}/seance', [GroupController::class, 'seancePrep']);
    Route::post('/groups/{group}/seance', [GroupController::class, 'saveSeance']);
    Route::put('/groups/{group}/seance/{session}', [GroupController::class, 'updateSeance']);

    Route::get('/search', [SearchController::class, 'index']);

    Route::get('/watchlist', [WatchlistController::class, 'index']);
    Route::post('/watchlist/toggle', [WatchlistController::class, 'toggle']);

    Route::get('/calendrier', [CalendrierController::class, 'index']);

    Route::get('/reglages', [ReglagesController::class, 'show']);
    Route::post('/reglages/visibility-global', [ReglagesController::class, 'updateGlobal']);
});
