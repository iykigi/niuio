<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuspendedController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (! $user->is_suspended) {
            return redirect()->route('dashboard');
        }

        return view('suspended', ['user' => $user]);
    }
}
