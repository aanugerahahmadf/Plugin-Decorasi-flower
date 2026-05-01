<?php

namespace Aanugerah\WeddingPro\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Menunggu'),
            self::CONFIRMED => __('Dikonfirmasi'),
            self::COMPLETED => __('Selesai'),
            self::CANCELLED => __('Dibatalkan'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::CONFIRMED => 'heroicon-m-check-circle',
            self::COMPLETED => 'heroicon-m-check-badge',
            self::CANCELLED => 'heroicon-m-x-circle',
        };
    }
}
