<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalletService
{
    /** Deposit creates balance if missing. */
    public function deposit(User $user, int $amountCents, ?string $comment = null): array
    {
        return DB::transaction(function () use ($user, $amountCents, $comment) {
            $balance = Balance::lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance_cents' => 0]
            );

            $before = $balance->balance_cents;
            $after  = $before + $amountCents;

            $balance->balance_cents = $after;
            $balance->save();

            $tx = Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Deposit,
                'amount_cents' => $amountCents,
                'balance_before_cents' => $before,
                'balance_after_cents' => $after,
                'comment' => $comment,
            ]);

            return ['balance_cents' => $after, 'transaction' => $tx];
        });
    }

    /** Withdraw fails if not enough funds or no balance. */
    public function withdraw(User $user, int $amountCents, ?string $comment = null): array
    {
        return DB::transaction(function () use ($user, $amountCents, $comment) {
            $balance = Balance::lockForUpdate()->where('user_id', $user->id)->first();
            if (!$balance || $balance->balance_cents < $amountCents) {
                throw new RuntimeException('insufficient_funds');
            }

            $before = $balance->balance_cents;
            $after  = $before - $amountCents;

            $balance->balance_cents = $after;
            $balance->save();

            $tx = Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Withdraw,
                'amount_cents' => $amountCents,
                'balance_before_cents' => $before,
                'balance_after_cents' => $after,
                'comment' => $comment,
            ]);

            return ['balance_cents' => $after, 'transaction' => $tx];
        });
    }

    /** Transfer: create receiver balance if missing. */
    public function transfer(User $from, User $to, int $amountCents, ?string $comment = null): array
    {
        if ($from->id === $to->id) {
            throw new RuntimeException('transfer_to_self_forbidden');
        }

        // Order locks to avoid deadlocks
        [$firstId, $secondId] = $from->id < $to->id ? [$from->id, $to->id] : [$to->id, $from->id];

        return DB::transaction(function () use ($from, $to, $amountCents, $comment, $firstId, $secondId) {
            $balances = Balance::lockForUpdate()->whereIn('user_id', [$firstId, $secondId])->get()->keyBy('user_id');

            $fromBalance = $balances->get($from->id) ?? new Balance(['user_id' => $from->id, 'balance_cents' => 0]);
            $toBalance   = $balances->get($to->id)   ?? new Balance(['user_id' => $to->id,   'balance_cents' => 0]);

            if (!$fromBalance->exists) {
                // not persisted yet, but no need to save if insufficient anyway
            }

            if ($fromBalance->balance_cents < $amountCents) {
                throw new RuntimeException('insufficient_funds');
            }

            $transferGroup = (string) Str::uuid();

            // from -> out
            $beforeFrom = $fromBalance->balance_cents;
            $afterFrom  = $beforeFrom - $amountCents;
            $fromBalance->balance_cents = $afterFrom;
            $fromBalance->save();

            $txOut = Transaction::create([
                'user_id' => $from->id,
                'type' => TransactionType::TransferOut,
                'amount_cents' => $amountCents,
                'balance_before_cents' => $beforeFrom,
                'balance_after_cents' => $afterFrom,
                'comment' => $comment,
                'transfer_group' => $transferGroup,
            ]);

            // to -> in (create if missing)
            if (!$toBalance->exists) {
                $toBalance->save();
            }
            $beforeTo = $toBalance->balance_cents;
            $afterTo  = $beforeTo + $amountCents;
            $toBalance->balance_cents = $afterTo;
            $toBalance->save();

            $txIn = Transaction::create([
                'user_id' => $to->id,
                'type' => TransactionType::TransferIn,
                'amount_cents' => $amountCents,
                'balance_before_cents' => $beforeTo,
                'balance_after_cents' => $afterTo,
                'comment' => $comment,
                'transfer_group' => $transferGroup,
            ]);

            return [
                'from' => ['balance_cents' => $afterFrom, 'transaction' => $txOut],
                'to'   => ['balance_cents' => $afterTo,   'transaction' => $txIn],
                'transfer_group' => $transferGroup,
            ];
        });
    }
}
