<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
final class OrderNotification extends Model
{
    protected function casts(): array
    {
        return [
            'type' => OrderStatus::class,
            'sent_at' => 'datetime',
        ];
    }
}
