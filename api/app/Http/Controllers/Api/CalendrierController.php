<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendrierController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $showIds = $user->showTrackings()->pluck('show_id')
            ->merge($user->watchlistItems()->pluck('show_id'))
            ->unique()->values();

        $episodes = Episode::whereIn('show_id', $showIds)
            ->whereNotNull('air_date')
            ->where('air_date', '>=', Carbon::today())
            ->with('show')
            ->orderBy('air_date')
            ->get();

        $groupsByShow = Group::whereIn('show_id', $showIds)
            ->whereNull('archived_at')
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->get()->keyBy('show_id');

        $agenda = $episodes->groupBy(fn (Episode $e) => $e->air_date->toDateString())
            ->map(function ($items, $date) use ($groupsByShow) {
                return [
                    'jour' => Carbon::parse($date)->locale('fr')->isoFormat('dddd D MMMM'),
                    'items' => $items->map(function (Episode $ep) use ($groupsByShow) {
                        $group = $groupsByShow->get($ep->show_id);
                        $note = null;

                        if ($group) {
                            $order = $group->pointCommunOrderIndex();
                            if ($order === null || $ep->order_index > $order) {
                                $note = 'Notification retenue — on protège le groupe';
                            }
                        }

                        return [
                            'code' => $ep->code(), 'serie' => $ep->show->title,
                            'plateforme' => $ep->show->platform, 'note' => $note,
                        ];
                    })->values(),
                ];
            })->values();

        return ['agenda' => $agenda];
    }
}
