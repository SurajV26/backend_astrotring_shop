<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdgebraAttributionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdgebraTrackingController extends Controller
{
    public function visit(Request $request)
    {
        $data = $request->validate([
            'visitor_key' => ['required', 'uuid'],
            'event_id' => ['required', 'uuid'],
            'tracking_id' => ['required', 'uuid'],
            'landing_page' => ['required', 'in:dhan-yog,rashi,rudraksha,bracelets,combos'],
            'landing_path' => ['required', 'string', 'max:255'],
            'utm_source' => ['required', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:191'],
            'utm_content' => ['nullable', 'string', 'max:191'],
            'utm_term' => ['nullable', 'string', 'max:191'],
            'click_id' => ['nullable', 'string', 'max:191'],
            'referrer_host' => ['nullable', 'string', 'max:255'],
        ]);

        if (strcasecmp(trim($data['utm_source']), 'Adgebra') !== 0) {
            return response()->json(['tracked' => false, 'message' => 'Not an Adgebra visit.'], 202);
        }

        $existing = DB::table('adgebra_visits')->where('event_id', $data['event_id'])->first();
        if ($existing) {
            return response()->json([
                'tracked' => true,
                'tracking_id' => $existing->tracking_id,
                'duplicate' => true,
            ]);
        }

        $now = now();
        try {
            DB::table('adgebra_visits')->insert([
                'tracking_id' => $data['tracking_id'],
                'event_id' => $data['event_id'],
                'visitor_key' => $data['visitor_key'],
                'landing_page' => $data['landing_page'],
                'landing_path' => $data['landing_path'],
                'source' => 'Adgebra',
                'medium' => $data['utm_medium'] ?? null,
                'campaign_id' => $data['utm_campaign'] ?? null,
                'placement_id' => $data['utm_content'] ?? null,
                'creative_id' => $data['utm_term'] ?? null,
                'click_id' => $data['click_id'] ?? null,
                'referrer_host' => $data['referrer_host'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (QueryException $exception) {
            // Idempotent retry when the same landing-page event reaches the API twice.
            $existing = DB::table('adgebra_visits')->where('event_id', $data['event_id'])->first();
            if (!$existing) {
                throw $exception;
            }
            return response()->json([
                'tracked' => true,
                'tracking_id' => $existing->tracking_id,
                'duplicate' => true,
            ]);
        }

        return response()->json(['tracked' => true, 'tracking_id' => $data['tracking_id']], 201);
    }

    public function click(Request $request)
    {
        $data = $request->validate([
            'visitor_key' => ['required', 'uuid'],
            'event_id' => ['required', 'uuid'],
            'tracking_id' => ['required', 'uuid'],
            'destination_url' => ['required', 'url', 'max:2048'],
        ]);

        $visit = DB::table('adgebra_visits')
            ->where('tracking_id', $data['tracking_id'])
            ->where('visitor_key', $data['visitor_key'])
            ->where('source', 'Adgebra')
            ->first();

        if (!$visit) {
            return response()->json(['tracked' => false], 404);
        }

        $host = strtolower((string) parse_url($data['destination_url'], PHP_URL_HOST));
        if (!in_array($host, ['astrotring.shop', 'www.astrotring.shop'], true)) {
            return response()->json(['tracked' => false, 'message' => 'External destinations are not recorded.'], 202);
        }

        DB::table('adgebra_clicks')->insertOrIgnore([
            'visit_id' => $visit->id,
            'tracking_id' => $visit->tracking_id,
            'visitor_key' => $data['visitor_key'],
            'event_id' => $data['event_id'],
            'destination_path' => (string) (parse_url($data['destination_url'], PHP_URL_PATH) ?: '/'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['tracked' => true]);
    }

    /** Attach the browser's Adgebra visit to the authenticated store account after OTP login. */
    public function identify(Request $request, AdgebraAttributionService $attributionService)
    {
        $data = $request->validate([
            'tracking_id' => ['required', 'uuid'],
            'visitor_key' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['identified' => false, 'message' => 'Authentication required.'], 401);
        }

        $identified = $attributionService->identifyVisitForAccount(
            $data['tracking_id'],
            $data['visitor_key'],
            (int) $user->id
        );

        if (!$identified) {
            return response()->json([
                'identified' => false,
                'message' => 'No eligible Adgebra visit was found for this browser, or it is already linked to another account.',
            ], 404);
        }

        return response()->json(['identified' => true]);
    }
}
