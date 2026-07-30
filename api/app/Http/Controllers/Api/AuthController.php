<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const PALETTE = ['#F0B091', '#AECBA0', '#A9C3E3', '#C9AEDC', '#EBCB8F'];

    /** "Créer un foyer" — a brand new compte, admin of its first foyer. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'foyer' => ['required', 'string', 'max:120'],
        ]);

        $household = Household::create(['name' => $data['foyer']]);

        $user = User::create([
            'current_household_id' => $household->id,
            'name' => $data['prenom'],
            'initial' => mb_strtoupper(mb_substr($data['prenom'], 0, 1)),
            'color' => self::PALETTE[0],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
            'visibility_global' => 'privee',
        ]);

        HouseholdMembership::create([
            'user_id' => $user->id, 'household_id' => $household->id,
            'role' => 'admin', 'joined_at' => now(),
        ]);

        HouseholdInvite::create([
            'household_id' => $household->id,
            'code' => 'FANCH-'.Str::upper(Str::random(6)),
        ]);

        return $this->tokenResponse($user);
    }

    /** "J'ai une invitation" — a brand new compte joining an existing foyer as membre. */
    public function join(Request $request)
    {
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'code' => ['required', 'string'],
        ]);

        $invite = HouseholdInvite::where('code', Str::upper(trim($data['code'])))->first();

        if (! $invite) {
            return response()->json(['message' => "Ce code d'invitation est introuvable."], 422);
        }

        $takenColors = $invite->household->members()->pluck('color')->all();
        $color = collect(self::PALETTE)->first(fn ($c) => ! in_array($c, $takenColors, true)) ?? self::PALETTE[0];

        $user = User::create([
            'current_household_id' => $invite->household_id,
            'name' => $data['prenom'],
            'initial' => mb_strtoupper(mb_substr($data['prenom'], 0, 1)),
            'color' => $color,
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
            'visibility_global' => 'privee',
        ]);

        HouseholdMembership::create([
            'user_id' => $user->id, 'household_id' => $invite->household_id,
            'role' => 'membre', 'joined_at' => now(),
        ]);

        return $this->tokenResponse($user);
    }

    public function me(Request $request)
    {
        return $this->userPayload($request->user());
    }

    private function tokenResponse(User $user): array
    {
        $token = $user->createToken('fanch-mobile')->plainTextToken;

        return ['token' => $token] + $this->userPayload($user);
    }

    private function userPayload(User $user): array
    {
        $user->load(['currentHousehold.members', 'households']);

        return [
            'user' => [
                'id' => $user->id, 'name' => $user->name, 'initial' => $user->initial, 'color' => $user->color,
            ],
            'household' => $user->currentHousehold ? [
                'id' => $user->currentHousehold->id, 'name' => $user->currentHousehold->name,
                'members' => $user->currentHousehold->members->map(fn (User $m) => [
                    'id' => $m->id, 'name' => $m->name, 'initial' => $m->initial, 'color' => $m->color,
                ]),
            ] : null,
            'households' => $user->households->map(fn (Household $h) => [
                'id' => $h->id, 'name' => $h->name, 'role' => $h->pivot->role,
            ]),
        ];
    }
}
