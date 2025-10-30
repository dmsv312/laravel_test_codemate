<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Requests\TransferRequest;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use RuntimeException;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $wallet) {}

    public function balance(User $user): JsonResponse
    {
        $cents = optional($user->balance)->balance_cents ?? 0;
        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'balance' => $this->formatAmount($cents),
            ],
            'meta' => [ 'timestamp' => now()->toIso8601String() ],
        ]);
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        $amountCents = $this->toCents($request->input('amount'));
        [$result] = [ $this->wallet->deposit($user, $amountCents, $request->input('comment')) ];

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'balance' => $this->formatAmount($result['balance_cents']),
                'transaction_id' => $result['transaction']->id,
                'type' => (string)$result['transaction']->type->value,
            ],
            'meta' => [ 'timestamp' => now()->toIso8601String() ],
        ]);
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->integer('user_id'));
        $amountCents = $this->toCents($request->input('amount'));
        try {
            [$result] = [ $this->wallet->withdraw($user, $amountCents, $request->input('comment')) ];
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'insufficient_funds') {
                return $this->error('insufficient_funds', 'Недостаточно средств', 409);
            }
            throw $e;
        }

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'balance' => $this->formatAmount($result['balance_cents']),
                'transaction_id' => $result['transaction']->id,
                'type' => (string)$result['transaction']->type->value,
            ],
            'meta' => [ 'timestamp' => now()->toIso8601String() ],
        ]);
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $from = User::findOrFail($request->integer('from_user_id'));
        $to   = User::findOrFail($request->integer('to_user_id'));
        $amountCents = $this->toCents($request->input('amount'));

        try {
            $result = $this->wallet->transfer($from, $to, $amountCents, $request->input('comment'));
        } catch (RuntimeException $e) {
            $code = $e->getMessage();
            if ($code === 'insufficient_funds') {
                return $this->error('insufficient_funds', 'Недостаточно средств', 409);
            } elseif ($code === 'transfer_to_self_forbidden') {
                return $this->error('transfer_to_self_forbidden', 'Перевод самому себе запрещён', 400);
            }
            throw $e;
        }

        return response()->json([
            'data' => [
                'transfer_group' => $result['transfer_group'],
                'from' => [
                    'user_id' => $from->id,
                    'balance' => $this->formatAmount($result['from']['balance_cents']),
                    'transaction_id' => $result['from']['transaction']->id,
                    'type' => (string)$result['from']['transaction']->type->value,
                ],
                'to' => [
                    'user_id' => $to->id,
                    'balance' => $this->formatAmount($result['to']['balance_cents']),
                    'transaction_id' => $result['to']['transaction']->id,
                    'type' => (string)$result['to']['transaction']->type->value,
                ]
            ],
            'meta' => [ 'timestamp' => now()->toIso8601String() ],
        ]);
    }

    private function toCents($amount): int
    {
        $s = (string) $amount;
        $s = str_replace(',', '.', $s);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $s)) {
            abort(response()->json(['error' => ['code' => 'invalid_amount', 'message' => 'Некорректная сумма']], 422));
        }
        $parts = explode('.', $s, 2);
        $cents = ((int)$parts[0]) * 100;
        if (isset($parts[1])) {
            $frac = str_pad(substr($parts[1], 0, 2), 2, '0');
            $cents += (int)$frac;
        }
        return $cents;
    }

    private function formatAmount(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $rub = intdiv($cents, 100);
        $kop = $cents % 100;
        return $sign . $rub . '.' . str_pad((string)$kop, 2, '0', STR_PAD_LEFT);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
