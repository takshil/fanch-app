<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\WatchMark;
use App\Services\AVoirService;
use App\Services\WatchMarkService;
use Illuminate\Http\Request;

class AVoirController extends Controller
{
    public function index(Request $request)
    {
        return ['items' => AVoirService::items($request->user())];
    }

    public function toggle(Request $request, Episode $episode)
    {
        $user = $request->user();
        $exists = WatchMark::where('user_id', $user->id)->where('episode_id', $episode->id)->exists();

        if ($exists) {
            WatchMarkService::clearMark($user, $episode);
            $vu = false;
        } else {
            WatchMarkService::recordViewing($user, $episode);
            $vu = true;
        }

        return ['episode_id' => $episode->id, 'vu' => $vu];
    }
}
