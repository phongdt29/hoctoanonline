<?php

namespace App\Services\Payment;

use InvalidArgumentException;

/** Ticket R3 — tao gateway tu config. Code nghiep vu goi make(), khong new truc tiep. */
class PaymentGatewayFactory
{
    public function make(?string $name = null): PaymentGateway
    {
        $name ??= config('payment.default');

        return match ($name) {
            'vnpay' => new VnpayGateway(
                (string) config('payment.vnpay.tmn_code'),
                (string) config('payment.vnpay.hash_secret'),
                (string) config('payment.vnpay.pay_url'),
            ),
            'momo' => new MomoGateway(
                (string) config('payment.momo.partner_code'),
                (string) config('payment.momo.access_key'),
                (string) config('payment.momo.secret_key'),
                (string) config('payment.momo.pay_url'),
            ),
            default => throw new InvalidArgumentException("Cong thanh toan khong ho tro: {$name}"),
        };
    }
}
