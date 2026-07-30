<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Show;
use App\Services\WatchMarkService;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function show(Request $request, Show $show)
    {
        $user = $request->user();
        $lastSeason = $show->episodes()->max('season_number');
        $episodes = $show->episodes()->where('season_number', $lastSeason)->get();
        $watchedIds = $user->watchMarks()->pluck('episode_id')->all();

        $eps = $episodes->map(fn ($ep) => [
            'id' => $ep->id, 'num' => 'E'.$ep->episode_number, 'titre' => $ep->title,
            'vu' => in_array($ep->id, $watchedIds, true),
        ]);

        $nbVus = $eps->where('vu', true)->count();
        $override = $user->showVisibilities()->where('show_id', $show->id)->value('visibility');
        $group = Group::forUserAndShow($user, $show);

        return [
            'show' => $show->card() + [
                'genre' => $show->genre, 'status' => $show->status, 'platform' => $show->platform,
            ],
            'season_number' => $lastSeason,
            'episodes' => $eps,
            'nb_vus' => $nbVus,
            'total' => $eps->count(),
            'pct_vus' => $eps->count() ? round($nbVus / $eps->count() * 100) : 0,
            'visibility' => $override ?? 'heritee',
            'group_id' => $group?->id,
        ];
    }

    public function setVisibility(Request $request, Show $show)
    {
        $data = $request->validate(['visibility' => ['required', 'in:heritee,privee,visible']]);
        $user = $request->user();

        if ($data['visibility'] === 'heritee') {
            $user->showVisibilities()->where('show_id', $show->id)->delete();
        } else {
            $user->showVisibilities()->updateOrCreate(
                ['show_id' => $show->id],
                ['visibility' => $data['visibility']]
            );
        }

        return ['visibility' => $data['visibility']];
    }

    public function markAllSeason(Request $request, Show $show)
    {
        $user = $request->user();
        $lastSeason = $show->episodes()->max('season_number');
        $episodes = $show->episodes()->where('season_number', $lastSeason)->get();
        $alreadyWatched = $user->watchMarks()->whereIn('episode_id', $episodes->pluck('id'))->pluck('episode_id')->all();

        foreach ($episodes as $episode) {
            if (! in_array($episode->id, $alreadyWatched, true)) {
                WatchMarkService::recordViewing($user, $episode);
            }
        }

        return ['ok' => true];
    }
}
