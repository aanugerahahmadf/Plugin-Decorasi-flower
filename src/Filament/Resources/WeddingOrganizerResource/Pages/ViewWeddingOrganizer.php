<?php

namespace Aanugerah\WeddingPro\Filament\Resources\WeddingOrganizerResource\Pages;

use Aanugerah\WeddingPro\Filament\Resources\WeddingOrganizerResource;
use Aanugerah\WeddingPro\Filament\User\Widgets\StatsOverview;
use Aanugerah\WeddingPro\Filament\User\Widgets\UnifiedHistoryWidget;
use Aanugerah\WeddingPro\Filament\User\Widgets\UserOrdersChart;
use Aanugerah\WeddingPro\Filament\User\Widgets\UserSpendingChart;
use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Filament\Resources\Pages\ViewRecord;

class ViewWeddingOrganizer extends ViewRecord
{
    protected static string $resource = WeddingOrganizerResource::class;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getResource()::getModel()::query()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getResource()::getNavigationLabel();
    }

    /**
     * Set the record to ID 1 automatically.
     */
    public function mount(int|string|null $record = null): void
    {
        // Langsung arahkan router ke record 1 (bisa asli atau placeholder memori)
        $this->record = $this->resolveRecord(1);

        parent::mount(1);
    }

    protected function resolveRecord(int|string $key): WeddingOrganizer
    {
        return WeddingOrganizer::getBrand() ?? new WeddingOrganizer;
    }

    /**
     * Set the record to ID 1 automatically.
     */
    public function getTitle(): string
    {
        return __('Beranda');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            UserOrdersChart::class,
            UserSpendingChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // UnifiedHistoryWidget::class,
        ];
    }
}
