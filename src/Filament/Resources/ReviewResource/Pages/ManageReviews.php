<?php

namespace Aanugerah\WeddingPro\Filament\Resources\ReviewResource\Pages;

use Aanugerah\WeddingPro\Filament\User\Concerns\HasMobilePagination;
use Aanugerah\WeddingPro\Filament\Resources\ReviewResource;
use Filament\Resources\Pages\ManageRecords;

class ManageReviews extends ManageRecords
{
    use HasMobilePagination;

    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
