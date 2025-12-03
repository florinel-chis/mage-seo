<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\SeoJob;
use App\Models\SeoDraft;
use App\Models\MagentoStore;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingReviewCount = SeoDraft::where('status', 'PENDING_REVIEW')->count();
        $approvedCount = SeoDraft::where('status', 'APPROVED')->count();
        $totalProducts = Product::count();
        $activeJobs = SeoJob::whereIn('status', ['PENDING', 'RUNNING'])->count();

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Products in catalog')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary')
                ->chart([7, 12, 15, 18, 20, 22, $totalProducts]),

            Stat::make('Active Jobs', $activeJobs)
                ->description('Running or pending')
                ->descriptionIcon('heroicon-o-clock')
                ->color($activeJobs > 0 ? 'warning' : 'success'),

            Stat::make('Pending Review', $pendingReviewCount)
                ->description('Drafts awaiting approval')
                ->descriptionIcon('heroicon-o-eye')
                ->color($pendingReviewCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.seo-drafts.index', ['tableFilters' => ['status' => ['value' => 'PENDING_REVIEW']]]))
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Approved', $approvedCount)
                ->description('Ready for sync')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
