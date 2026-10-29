<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class OrderNotification extends Model
{
    protected function casts(): array
    {
        return [
            'type' => OrderStatus::class,
            'sent_at' => 'datetime',
        ];
    }
}
