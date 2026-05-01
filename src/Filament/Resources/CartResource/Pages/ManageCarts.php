<?php

namespace Aanugerah\WeddingPro\Filament\Resources\CartResource\Pages;

use Aanugerah\WeddingPro\Filament\User\Concerns\HasMobilePagination;
use Aanugerah\WeddingPro\Filament\Resources\CartResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCarts extends ManageRecords
{
    use HasMobilePagination;

    protected static string $resource = CartResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
