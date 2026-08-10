<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SignupRoleController extends Controller
{
    public function show()
    {
        return view('auth.signup-role', [
            'selectedRole' => session('signup.role'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => ['required', 'in:user,photographer'],
        ]);

        session()->put('signup.role', $request->role);

        return redirect()->route('register.details');
    }
}
