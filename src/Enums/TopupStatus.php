<?php

namespace Aanugerah\WeddingPro\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TopupStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Tertunda'),
            self::SUCCESS => __('Berhasil'),
            self::FAILED => __('Gagal'),
            self::CANCELLED => __('Dibatalkan'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::SUCCESS => 'heroicon-m-check-circle',
            self::FAILED => 'heroicon-m-x-circle',
            self::CANCELLED => 'heroicon-m-x-circle',
        };
    }
}
