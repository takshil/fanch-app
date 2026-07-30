<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HouseholdController extends Controller
{
    /** §3.1: a compte can create (and so belong to) more than one foyer. */
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $user = $request->user();

        $household = Household::create(['name' => $data['name']]);
        HouseholdMembership::create([
            'user_id' => $user->id, 'household_id' => $household->id,
            'role' => 'admin', 'joined_at' => now(),
        ]);
        HouseholdInvite::create(['household_id' => $household->id, 'code' => 'FANCH-'.Str::upper(Str::random(6))]);
        $user->update(['current_household_id' => $household->id]);

        return $this->context($user->fresh());
    }

    /** Join an additional foyer via its invite code — the compte keeps its existing progression. */
    public function join(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        $invite = HouseholdInvite::where('code', Str::upper(trim($data['code'])))->first();
        abort_unless($invite, 422, "Ce code d'invitation est introuvable.");

        HouseholdMembership::firstOrCreate(
            ['user_id' => $user->id, 'household_id' => $invite->household_id],
            ['role' => 'membre', 'joined_at' => now()]
        );
        $user->update(['current_household_id' => $invite->household_id]);

        return $this->context($user->fresh());
    }

    /** Switch which foyer is active in the app right now. */
    public function switch(Request $request, Household $household)
    {
        $user = $request->user();
        $this->authorizeMembership($request, $household);

        $user->update(['current_household_id' => $household->id]);

        return $this->context($user->fresh());
    }

    /** Leaving cuts visibility for that foyer's members without touching any data (§3.6) — the progression stays on the compte. */
    public function leave(Request $request, Household $household)
    {
        $user = $request->user();
        $membership = $this->authorizeMembership($request, $household);

        abort_if($user->households()->count() <= 1, 422, 'Impossible de quitter votre dernier foyer.');

        $membership->delete();

        if ($user->current_household_id === $household->id) {
            $next = $user->households()->first();
            $user->update(['current_household_id' => $next?->id]);
        }

        return $this->context($user->fresh());
    }

    private function authorizeMembership(Request $request, Household $household): HouseholdMembership
    {
        $membership = HouseholdMembership::where('user_id', $request->user()->id)
            ->where('household_id', $household->id)->first();
        abort_unless($membership, 403);

        return $membership;
    }

    public static function context(User $user): array
    {
        $user->load('households');

        return [
            'user' => ['id' => $user->id, 'name' => $user->name, 'initial' => $user->initial, 'color' => $user->color],
            'current_household_id' => $user->current_household_id,
            'households' => $user->households->map(fn (Household $h) => [
                'id' => $h->id, 'name' => $h->name, 'role' => $h->pivot->role,
            ]),
        ];
    }
}
