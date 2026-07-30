<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /** Palette used to give freshly-discovered (TVmaze-only) shows a colour before the user adds them. */
    private const PALETTE = ['#A9C3E3', '#C9AEDC', '#EBCB8F', '#AECBA0', '#B7AB96'];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();

        if ($q === '') {
            return ['query' => $q, 'results' => []];
        }

        $watchlistedShowIds = $user->watchlistItems()->pluck('show_id')->all();

        $local = Show::where('title', 'like', '%'.$q.'%')->limit(10)->get();
        $results = $local->map(fn (Show $show) => $show->card() + [
            'source' => 'local',
            'added' => in_array($show->id, $watchlistedShowIds, true),
        ])->values()->all();

        $knownSlugs = $local->pluck('slug')->all();

        foreach ($this->tvmazeResults($q) as $tv) {
            $slug = Str::slug($tv['title']);
            if (in_array($slug, $knownSlugs, true)) {
                continue; // already have a local match for this show
            }
            $results[] = [
                'id' => null, 'slug' => $slug, 'title' => $tv['title'],
                'initiale' => mb_strtoupper(mb_substr($tv['title'], 0, 1)),
                'color' => self::PALETTE[crc32($tv['title']) % count(self::PALETTE)],
                'info' => $tv['info'],
                'source' => 'tvmaze', 'tvmaze_id' => $tv['tvmaze_id'],
                'platform' => $tv['platform'], 'seasons_count' => $tv['seasons_count'],
                'added' => false,
            ];
        }

        return ['query' => $q, 'results' => array_slice($results, 0, 8)];
    }

    /** Best-effort: TVmaze is a free, keyless catalogue, but may be unreachable from this network. */
    private function tvmazeResults(string $q): array
    {
        try {
            $response = Http::timeout(4)->get('https://api.tvmaze.com/search/shows', ['q' => $q]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json())->take(6)->map(function ($row) {
                $show = $row['show'] ?? [];

                return [
                    'title' => $show['name'] ?? 'Sans titre',
                    'tvmaze_id' => $show['id'] ?? null,
                    'platform' => $show['network']['name'] ?? $show['webChannel']['name'] ?? null,
                    'seasons_count' => 1,
                    'info' => trim(($show['network']['name'] ?? $show['webChannel']['name'] ?? '').
                        ($show['premiered'] ? ' · '.substr($show['premiered'], 0, 4) : '')),
                ];
            })->all();
        } catch (\Throwable $e) {
            Log::info('TVmaze search unavailable, falling back to local catalogue only.', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
