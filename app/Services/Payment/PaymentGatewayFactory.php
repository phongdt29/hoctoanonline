<?php

namespace App\Services\Payment;

use InvalidArgumentException;

/** Ticket R3 — tao gateway tu config. Code nghiep vu goi make(), khong new truc tiep. */
class PaymentGatewayFactory
{
    /** Cac cong DA cau hinh (co du credentials) — de UI chi hien nut cong dung duoc. */
    public function configured(): array
    {
        $out = [];

        if (config('payment.vnpay.tmn_code') && config('payment.vnpay.hash_secret')) {
            $out[] = 'vnpay';
        }

        if (config('payment.momo.partner_code') && config('payment.momo.access_key') && config('payment.momo.secret_key')) {
            $out[] = 'momo';
        }

        return $out;
    }

    public function isConfigured(string $name): bool
    {
        return in_array($name, $this->configured(), true);
    }

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
