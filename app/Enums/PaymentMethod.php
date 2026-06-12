<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Zarinpal = 'zarinpal';
    case CardToCard = 'card_to_card';
    case Installment = 'installment';
    case External = 'external';
}
