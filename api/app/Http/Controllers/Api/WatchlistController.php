<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Show;
use App\Models\ShowTracking;
use App\Models\WatchlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WatchlistController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->watchlistItems()->with('show')->get();

        return ['items' => $items->map(fn (WatchlistItem $i) => $i->show->card())];
    }

    /** Add or remove a show from the watchlist. Accepts a local show_id, or TVmaze details to create it first. */
    public function toggle(Request $request)
    {
        $data = $request->validate([
            'show_id' => ['nullable', 'integer', 'exists:shows,id'],
            'tvmaze_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
            'seasons_count' => ['nullable', 'integer'],
        ]);

        $show = $data['show_id']
            ? Show::findOrFail($data['show_id'])
            : $this->findOrCreateFromTvmaze($data);

        $user = $request->user();
        $existing = $user->watchlistItems()->where('show_id', $show->id)->first();

        if ($existing) {
            $existing->delete();
            $added = false;
        } else {
            $user->watchlistItems()->create(['show_id' => $show->id]);
            $added = true;
        }

        return ['show_id' => $show->id, 'added' => $added];
    }

    /** "Commencer" — move a watchlist show into active tracking (the "À voir" list). */
    public function start(Request $request, Show $show)
    {
        $user = $request->user();
        $user->watchlistItems()->where('show_id', $show->id)->delete();
        ShowTracking::firstOrCreate(['user_id' => $user->id, 'show_id' => $show->id]);

        return ['slug' => $show->slug];
    }

    private function findOrCreateFromTvmaze(array $data): Show
    {
        if (empty($data['tvmaze_id']) || empty($data['title'])) {
            abort(422, 'show_id ou (tvmaze_id + title) requis.');
        }

        return Show::firstOrCreate(
            ['tvmaze_id' => $data['tvmaze_id']],
            [
                'slug' => Str::slug($data['title']).'-'.$data['tvmaze_id'],
                'title' => $data['title'],
                'platform' => $data['platform'] ?? null,
                'seasons_count' => $data['seasons_count'] ?? 1,
                'color' => '#A9C3E3',
            ]
        );
    }
}
