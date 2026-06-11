<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'base_plan_id',
        'transaction_id',
        'original_transaction_id',
        'purchase_token',
        'status',
        'purchased_at',
        'expires_at',
        'verified_at',
        'raw_payload',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
