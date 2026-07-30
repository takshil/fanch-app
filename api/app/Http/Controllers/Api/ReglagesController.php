<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReglagesController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $exceptions = $user->showVisibilities()->with('show')->get();

        return [
            'visibility_global' => $user->visibility_global,
            'exceptions' => $exceptions->map(fn ($e) => [
                'show' => ['title' => $e->show->title], 'visibility' => $e->visibility,
            ]),
        ];
    }

    public function updateGlobal(Request $request)
    {
        $data = $request->validate(['visibility' => ['required', 'in:privee,foyers,groupes']]);
        $user = $request->user();
        $user->update(['visibility_global' => $data['visibility']]);

        return ['visibility_global' => $user->visibility_global];
    }
}
