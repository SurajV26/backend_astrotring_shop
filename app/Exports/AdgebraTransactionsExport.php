<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdgebraTransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Builder $reportQuery)
    {
    }

    public function query(): Builder
    {
        return $this->reportQuery;
    }

    public function headings(): array
    {
        return [
            'Order ID', 'Order Number', 'Order Date', 'Source', 'Medium', 'Landing Page', 'Campaign ID',
            'Placement ID', 'Creative ID', 'Click ID', 'Products', 'Order Amount (INR)',
            'Order Status', 'Payment Method', 'Payment Status', 'Gateway Transaction ID',
        ];
    }

    private function safeText($value): string
    {
        $text = (string) ($value ?? '');
        return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $text) ? "'" . $text : $text;
    }

    public function map($row): array
    {
        return [
            $row->order_id,
            $this->safeText($row->order_number),
            $row->order_date,
            $this->safeText($row->source),
            $this->safeText($row->medium),
            $this->safeText($row->landing_page),
            $this->safeText($row->campaign_id),
            $this->safeText($row->placement_id),
            $this->safeText($row->creative_id),
            $this->safeText($row->click_id),
            $this->safeText($row->products),
            (float) $row->total_amount,
            $this->safeText($row->order_status),
            $this->safeText($row->payment_method),
            $this->safeText($row->payment_status),
            $this->safeText($row->transaction_id),
        ];
    }
}
