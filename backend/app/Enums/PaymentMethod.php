<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CHEQUE = 'cheque';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'Cash',
            self::CHEQUE => 'Cheque',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CREDIT_CARD => 'Credit Card'
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::CASH => 'dollar-sign',
            self::CHEQUE => 'file-text',
            self::BANK_TRANSFER => 'credit-card',
            self::CREDIT_CARD => 'credit-card'
        };
    }
}