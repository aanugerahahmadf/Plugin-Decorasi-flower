<?php

namespace Aanugerah\WeddingPro\Filament\Resources\ArticleResource\Pages;

use Aanugerah\WeddingPro\Filament\User\Concerns\HasMobilePagination;
use Aanugerah\WeddingPro\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\ManageRecords;

class ManageArticles extends ManageRecords
{
    use HasMobilePagination;

    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
