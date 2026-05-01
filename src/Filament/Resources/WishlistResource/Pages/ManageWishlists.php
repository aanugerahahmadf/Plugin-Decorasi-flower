<?php

namespace Aanugerah\WeddingPro\Filament\Resources\WishlistResource\Pages;

use Aanugerah\WeddingPro\Filament\User\Concerns\HasMobilePagination;
use Aanugerah\WeddingPro\Filament\Resources\WishlistResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWishlists extends ManageRecords
{
    use HasMobilePagination;

    protected static string $resource = WishlistResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
