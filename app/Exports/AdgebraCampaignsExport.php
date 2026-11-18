<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdgebraCampaignsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Builder $reportQuery)
    {
    }

    public function query(): Builder
    {
        return $this->reportQuery->orderByDesc('visits');
    }

    public function headings(): array
    {
        return [
            'Landing Page', 'Source', 'Medium', 'Campaign ID', 'Placement ID', 'Creative ID',
            'Visits', 'Unique Visitors', 'CTA Clicks', 'Orders', 'Conversion Rate (%)', 'Order Value (INR)',
        ];
    }

    private function safeText($value): string
    {
        $text = (string) ($value ?? '');
        return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $text) ? "'" . $text : $text;
    }

    public function map($row): array
    {
        $uniqueVisitors = (int) $row->unique_visitors;
        $conversionRate = $uniqueVisitors > 0
            ? round(((int) $row->orders / $uniqueVisitors) * 100, 2)
            : 0;

        return [
            $this->safeText($row->landing_page),
            $this->safeText($row->source),
            $this->safeText($row->medium),
            $this->safeText($row->campaign_id),
            $this->safeText($row->placement_id),
            $this->safeText($row->creative_id),
            (int) $row->visits,
            $uniqueVisitors,
            (int) $row->clicks,
            (int) $row->orders,
            $conversionRate,
            (float) $row->order_value,
        ];
    }
}
