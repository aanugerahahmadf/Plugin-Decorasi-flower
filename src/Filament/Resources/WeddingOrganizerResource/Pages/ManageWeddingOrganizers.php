<?php

namespace Aanugerah\WeddingPro\Filament\Resources\WeddingOrganizerResource\Pages;

use Aanugerah\WeddingPro\Filament\User\Concerns\HasMobilePagination;
use Aanugerah\WeddingPro\Filament\Resources\WeddingOrganizerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWeddingOrganizers extends ManageRecords
{
    use HasMobilePagination;

    protected static string $resource = WeddingOrganizerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
