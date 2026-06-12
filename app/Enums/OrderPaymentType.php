<?php

namespace App\Enums;

enum OrderPaymentType: string
{
    case Cash = 'cash';
    case Installment = 'installment';
    case CardToCard = 'card_to_card';
    case External = 'external';
}
