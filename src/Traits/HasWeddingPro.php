<?php

namespace Aanugerah\WeddingPro\Traits;

use Aanugerah\WeddingPro\Models\Order;
use Aanugerah\WeddingPro\Models\Wishlist;
use Aanugerah\WeddingPro\Models\Transaction;
use Aanugerah\WeddingPro\Models\Voucher;

trait HasWeddingPro
{
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class, 'user_vouchers')
            ->withPivot('claimed_at', 'used_at', 'order_id')
            ->withTimestamps();
    }
}
