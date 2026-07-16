<?php

namespace App\Services\Payment;

/**
 * Ticket R3 — cong VNPAY (theo tai lieu sandbox vnpayment.vn).
 *
 * Chu ky HMAC-SHA512 tren hashdata = cac tham so sap xep theo key, urlencode,
 * noi bang & (dung dinh dang http_build_query — khop demo chinh thuc VNPAY).
 * verifyCallback tinh lai chu ky va so bang hash_equals (constant-time).
 */
class VnpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $tmnCode,
        private readonly string $hashSecret,
        private readonly string $payUrl,
    ) {}

    public function createPayment(string $orderId, int $amount, string $returnUrl, string $ipAddr = '127.0.0.1'): string
    {
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => $amount * 100,          // VNPAY tinh theo xu (VND * 100)
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $orderId,
            'vnp_OrderInfo' => "Thanh toan don hang {$orderId}",
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => $ipAddr,
            'vnp_CreateDate' => now()->format('YmdHis'),
        ];

        ksort($params);
        $hashData = http_build_query($params);
        $signature = hash_hmac('sha512', $hashData, $this->hashSecret);

        return $this->payUrl.'?'.$hashData.'&vnp_SecureHash='.$signature;
    }

    public function verifyCallback(array $payload): bool
    {
        $received = $payload['vnp_SecureHash'] ?? '';

        $data = collect($payload)
            ->except(['vnp_SecureHash', 'vnp_SecureHashType'])
            ->sortKeys()
            ->all();

        $expected = hash_hmac('sha512', http_build_query($data), $this->hashSecret);

        return $received !== '' && hash_equals($expected, $received);
    }

    public function extractOrderId(array $payload): ?string
    {
        return $payload['vnp_TxnRef'] ?? null;
    }

    public function extractAmount(array $payload): ?int
    {
        // VNPAY tra amount theo xu -> chia 100 ve VND.
        return isset($payload['vnp_Amount']) ? (int) ($payload['vnp_Amount'] / 100) : null;
    }

    public function isSuccessful(array $payload): bool
    {
        // Ca ma giao dich (vnp_ResponseCode) va ma thanh toan (vnp_TransactionStatus)
        // deu phai 00 (chuan VNPAY).
        return ($payload['vnp_ResponseCode'] ?? '') === '00'
            && ($payload['vnp_TransactionStatus'] ?? '00') === '00';
    }
}
