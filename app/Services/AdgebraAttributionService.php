<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdgebraAttributionService
{
    /** Link a stored landing visit to a logged-in store customer. */
    public function identifyVisitForAccount(string $trackingId, string $visitorKey, int $userId): bool
    {
        if ($userId <= 0 || !Str::isUuid($trackingId) || !Str::isUuid($visitorKey)) {
            return false;
        }

        $visit = DB::table('adgebra_visits')
            ->where('tracking_id', $trackingId)
            ->where('visitor_key', $visitorKey)
            ->where('source', 'Adgebra')
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if (!$visit) {
            return false;
        }

        return $this->identifyVisitForUser($visit, $userId);
    }

    /** Store the attribution against the Razorpay order before checkout opens. */
    public function storeRazorpayOrder(string $razorpayOrderId, ?string $trackingId): void
    {
        if (trim($razorpayOrderId) === '') {
            return;
        }

        $visit = $this->findValidVisit($trackingId);
        if (!$visit) {
            return;
        }

        $userId = (int) (auth()->id() ?: (request()->user()->id ?? 0));
        if ($userId > 0 && !$this->identifyVisitForUser($visit, $userId)) {
            return;
        }

        $now = now();
        DB::table('adgebra_order_attributions')->updateOrInsert(
            ['razorpay_order_id' => $razorpayOrderId],
            [
                'order_id' => null,
                'visit_id' => $visit->id,
                'tracking_id' => $visit->tracking_id,
                'attributed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /** Attach an eligible Adgebra visit to the final COD/Razorpay order. */
    public function attachToOrder(int $orderId, ?string $trackingId = null, ?string $razorpayOrderId = null): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $pending = null;
        if ($razorpayOrderId) {
            $pending = DB::table('adgebra_order_attributions')
                ->where('razorpay_order_id', $razorpayOrderId)
                ->first();
        }

        $visit = $pending
            ? $this->findValidVisit($pending->tracking_id)
            : $this->findValidVisit($trackingId);

        if (!$visit) {
            return false;
        }

        $orderUserId = (int) DB::table('orders')->where('id', $orderId)->value('user_id');
        if ($orderUserId <= 0 || !$this->identifyVisitForUser($visit, $orderUserId)) {
            return false;
        }

        $values = [
            'order_id' => $orderId,
            'visit_id' => $visit->id,
            'tracking_id' => $visit->tracking_id,
            'razorpay_order_id' => $razorpayOrderId ?: ($pending->razorpay_order_id ?? null),
            'attributed_at' => now(),
            'updated_at' => now(),
        ];

        if ($pending) {
            DB::table('adgebra_order_attributions')->where('id', $pending->id)->update($values);
        } else {
            $values['created_at'] = now();
            DB::table('adgebra_order_attributions')->updateOrInsert(['order_id' => $orderId], $values);
        }

        return true;
    }

    private function identifyVisitForUser(object $visit, int $userId): bool
    {
        if ($userId <= 0 || (!empty($visit->user_id) && (int) $visit->user_id !== $userId)) {
            return false;
        }

        $updates = [
            'user_id' => $userId,
            'updated_at' => now(),
        ];
        if (empty($visit->identified_at)) {
            $updates['identified_at'] = now();
        }

        DB::table('adgebra_visits')
            ->where('id', $visit->id)
            ->where(function ($query) use ($userId) {
                $query->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->update($updates);

        return (int) DB::table('adgebra_visits')
            ->where('id', $visit->id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function findValidVisit(?string $trackingId): ?object
    {
        if (!$trackingId || !Str::isUuid($trackingId)) {
            return null;
        }

        return DB::table('adgebra_visits')
            ->where('tracking_id', $trackingId)
            ->where('source', 'Adgebra')
            ->where('created_at', '>=', now()->subDays(30))
            ->first();
    }
}
