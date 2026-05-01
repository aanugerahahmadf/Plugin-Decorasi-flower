<?php

namespace Aanugerah\WeddingPro\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WithdrawalStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPLETED = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Tertunda'),
            self::APPROVED => __('Disetujui'),
            self::REJECTED => __('Ditolak'),
            self::COMPLETED => __('Selesai'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::COMPLETED => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::APPROVED => 'heroicon-m-check-circle',
            self::REJECTED => 'heroicon-m-x-circle',
            self::COMPLETED => 'heroicon-m-check-badge',
        };
    }
}
