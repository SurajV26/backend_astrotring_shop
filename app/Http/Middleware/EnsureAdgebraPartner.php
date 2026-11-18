<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdgebraPartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $partnerId = $request->session()->get('adgebra_partner_id');

        if (!$partnerId) {
            return redirect()->route('adgebra.login');
        }

        $partner = DB::table('adgebra_partners')
            ->where('id', $partnerId)
            ->where('is_active', 1)
            ->first();

        if (!$partner) {
            $request->session()->forget('adgebra_partner_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('adgebra.login');
        }

        $request->attributes->set('adgebra_partner', $partner);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
