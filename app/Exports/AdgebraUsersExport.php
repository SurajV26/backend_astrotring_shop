<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdgebraUsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Builder $reportQuery)
    {
    }

    public function query(): Builder
    {
        // Maatwebsite Excel reads FromQuery exports in chunks. Laravel requires
        // a deterministic ORDER BY for chunk(), so sort by the unique user key.
        return (clone $this->reportQuery)->orderBy('user_id', 'asc');
    }

    public function headings(): array
    {
        return ['User ID', 'Customer', 'Mobile (masked)', 'Email (masked)', 'Registered At', 'First Identified', 'Last Identified', 'Landing Pages', 'Campaign IDs', 'Placement IDs', 'Identified Visits'];
    }

    public function map($row): array
    {
        return [
            $row->user_id,
            $this->safeText($row->customer_name),
            $this->maskMobile($row->mobile ?? null),
            $this->maskEmail($row->email ?? null),
            $row->registered_at,
            $row->first_identified_at,
            $row->last_identified_at,
            $this->safeText($row->landing_pages),
            $this->safeText($row->campaign_ids),
            $this->safeText($row->placement_ids),
            (int) $row->linked_visits,
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
