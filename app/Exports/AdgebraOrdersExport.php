<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdgebraOrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Builder $reportQuery)
    {
    }

    public function query(): Builder
    {
        // Order ID is unique and gives chunked XLSX export a stable ordering.
        return (clone $this->reportQuery)->orderBy('order_id', 'asc');
    }

    public function headings(): array
    {
        return ['Order ID', 'Order Number', 'Order Date', 'User ID', 'Customer', 'Mobile (masked)', 'Email (masked)', 'Products', 'Quantity', 'Order Amount (INR)', 'Order Status', 'Payment Method', 'Payment Status', 'Transaction ID', 'Landing Page', 'Campaign ID', 'Placement ID', 'Creative ID', 'Click ID'];
    }

    public function map($row): array
    {
        return [
            $row->order_id,
            $this->safeText($row->order_number),
            $row->order_date,
            $row->user_id,
            $this->safeText($row->customer_name),
            $this->maskMobile($row->mobile ?? null),
            $this->maskEmail($row->email ?? null),
            $this->safeText($row->products),
            (int) ($row->total_quantity ?? 0),
            (float) $row->total_amount,
            $this->safeText($row->order_status),
            $this->safeText($row->payment_method),
            $this->safeText($row->payment_status),
            $this->safeText($row->transaction_id),
            $this->safeText($row->landing_page),
            $this->safeText($row->campaign_id),
            $this->safeText($row->placement_id),
            $this->safeText($row->creative_id),
            $this->safeText($row->click_id),
        ];
    }

    private function safeText($value): string
    {
        $text = (string) ($value ?? '');
        return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $text) ? "'" . $text : $text;
    }

    private function maskMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        return $digits === '' ? '—' : str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    private function maskEmail(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || !str_contains($email, '@')) return '—';
        [$local, $domain] = explode('@', $email, 2);
        return mb_substr($local, 0, 1) . str_repeat('*', max(2, mb_strlen($local) - 1)) . '@' . $domain;
    }
}
