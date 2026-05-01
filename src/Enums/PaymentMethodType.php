<?php

namespace Aanugerah\WeddingPro\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethodType: string implements HasColor, HasIcon, HasLabel
{
    case BANK_TRANSFER = 'bank_transfer';
    case EWALLET = 'ewallet';
    case QRIS = 'qris';
    case COD = 'cod';
    case WALLET = 'wallet';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BANK_TRANSFER => __('Transfer Bank'),
            self::EWALLET => __('E-Wallet'),
            self::QRIS => __('QRIS'),
            self::COD => __('Cash On Delivery (COD)'),
            self::WALLET => __('Saldo Dompet (Topup)'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BANK_TRANSFER => 'info',
            self::EWALLET => 'warning',
            self::QRIS => 'success',
            self::COD => 'gray',
            self::WALLET => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'heroicon-m-building-library',
            self::EWALLET => 'heroicon-m-wallet',
            self::QRIS => 'heroicon-m-qr-code',
            self::COD => 'heroicon-m-banknotes',
            self::WALLET => 'heroicon-m-credit-card',
        };
    }
}
