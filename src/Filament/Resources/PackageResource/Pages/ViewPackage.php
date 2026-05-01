<?php

namespace Aanugerah\WeddingPro\Filament\Resources\PackageResource\Pages;

use Aanugerah\WeddingPro\Filament\Resources\PackageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPackage extends ViewRecord
{
    protected static string $resource = PackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit/delete for users ideally, maybe a "Book Now" or "Chat"
        ];
    }
}
