<?php

namespace App\Http\Controllers\Adgebra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartnerAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->has('adgebra_partner_id')) {
            return redirect()->route('adgebra.dashboard');
        }

        return response()->view('adgebra.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $partner = DB::table('adgebra_partners')
            ->where('username', trim($validated['username']))
            ->where('is_active', 1)
            ->first();

        if (!$partner || !Hash::check($validated['password'], $partner->password)) {
            return back()
                ->withErrors(['username' => 'Invalid username or password.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('adgebra_partner_id', $partner->id);

        DB::table('adgebra_partners')
            ->where('id', $partner->id)
            ->update(['last_login_at' => now(), 'updated_at' => now()]);

        return redirect()->intended(route('adgebra.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('adgebra_partner_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('adgebra.login')->with('status', 'You have been logged out.');
    }
}
