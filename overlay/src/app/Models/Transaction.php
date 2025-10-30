<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\TransactionType;

class Transaction extends Model
{
    protected $fillable = [
        'user_id','type','amount_cents','balance_before_cents','balance_after_cents','comment','transfer_group'
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'transfer_group' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
