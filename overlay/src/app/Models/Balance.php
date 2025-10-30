<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    protected $fillable = ['user_id', 'balance_cents'];

    public $timestamps = false;

    public function user(): BelongsTo
    {
      return $this->belongsTo(User::class);
    }
}
