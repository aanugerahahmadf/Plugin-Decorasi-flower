<?php

namespace Aanugerah\WeddingPro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class History extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'transaction_id',
        'reference_number',
        'amount',
        'info',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
