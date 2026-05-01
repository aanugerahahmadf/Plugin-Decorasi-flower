<?php

namespace Aanugerah\WeddingPro\Filament\Resources\ProductResource\Pages;

use Aanugerah\WeddingPro\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
