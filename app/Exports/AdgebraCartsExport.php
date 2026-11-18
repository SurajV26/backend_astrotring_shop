<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdgebraCartsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Builder $reportQuery)
    {
    }

    public function query(): Builder
    {
        // Cart ID is unique and gives chunked XLSX export a stable ordering.
        return (clone $this->reportQuery)->orderBy('cart_id', 'asc');
    }

    public function headings(): array
    {
        return ['Cart ID', 'User ID', 'Customer', 'Mobile (masked)', 'Email (masked)', 'Products', 'Product Lines', 'Quantity', 'Cart Value (INR)', 'Last Cart Activity', 'Landing Pages', 'Campaign IDs', 'Placement IDs', 'Creative IDs'];
    }

    public function map($row): array
    {
        return [
            $row->cart_id,
            $row->user_id,
            $this->safeText($row->customer_name),
            $this->maskMobile($row->mobile ?? null),
            $this->maskEmail($row->email ?? null),
            $this->safeText($row->products),
            (int) $row->product_lines,
            (int) $row->total_quantity,
            (float) $row->cart_value,
            $row->last_cart_activity,
            $this->safeText($row->landing_pages),
            $this->safeText($row->campaign_ids),
            $this->safeText($row->placement_ids),
            $this->safeText($row->creative_ids),
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
