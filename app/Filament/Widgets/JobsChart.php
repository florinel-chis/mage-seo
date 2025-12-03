<?php

namespace App\Filament\Widgets;

use App\Models\SeoJob;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class JobsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'SEO Job Activity';

    protected ?string $description = 'Jobs created over the last 7 days';

    protected function getData(): array
    {
        $data = SeoJob::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dates = array_map(fn($date) => Carbon::parse($date)->format('M d'), array_keys($data));
        $counts = array_values($data);

        return [
            'datasets' => [
                [
                    'label' => 'Jobs Created',
                    'data' => $counts,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
