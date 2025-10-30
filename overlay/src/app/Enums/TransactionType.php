<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
}
