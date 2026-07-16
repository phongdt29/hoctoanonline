<?php

namespace App\Services\Payment;

/**
 * Ticket R3 — cong MOMO.
 * Chu ky HMAC-SHA256 tren chuoi raw theo thu tu field co dinh cua MOMO.
 */
class MomoGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $partnerCode,
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $payUrl,
    ) {}

    public function createPayment(string $orderId, int $amount, string $returnUrl): string
    {
        $requestId = $orderId.'-'.now()->timestamp;
        $orderInfo = "Thanh toan don {$orderId}";
        $extraData = '';

        // MOMO ky theo thu tu field CO DINH (khong sort).
        $raw = "accessKey={$this->accessKey}&amount={$amount}&extraData={$extraData}"
            ."&ipnUrl={$returnUrl}&orderId={$orderId}&orderInfo={$orderInfo}"
            ."&partnerCode={$this->partnerCode}&redirectUrl={$returnUrl}"
            ."&requestId={$requestId}&requestType=captureWallet";

        $signature = hash_hmac('sha256', $raw, $this->secretKey);

        // That se POST JSON len MOMO va nhan payUrl; day tra URL mo phong.
        return $this->payUrl.'?orderId='.$orderId.'&signature='.$signature;
    }

    public function verifyCallback(array $payload): bool
    {
        $received = $payload['signature'] ?? '';

        $raw = "accessKey={$this->accessKey}"
            .'&amount='.($payload['amount'] ?? '')
            .'&extraData='.($payload['extraData'] ?? '')
            .'&message='.($payload['message'] ?? '')
            .'&orderId='.($payload['orderId'] ?? '')
            .'&orderInfo='.($payload['orderInfo'] ?? '')
            .'&orderType='.($payload['orderType'] ?? '')
            .'&partnerCode='.($payload['partnerCode'] ?? '')
            .'&payType='.($payload['payType'] ?? '')
            .'&requestId='.($payload['requestId'] ?? '')
            .'&responseTime='.($payload['responseTime'] ?? '')
            .'&resultCode='.($payload['resultCode'] ?? '')
            .'&transId='.($payload['transId'] ?? '');

        $expected = hash_hmac('sha256', $raw, $this->secretKey);

        return $received !== '' && hash_equals($expected, $received);
    }

    public function extractOrderId(array $payload): ?string
    {
        return $payload['orderId'] ?? null;
    }

    public function extractAmount(array $payload): ?int
    {
        // MOMO tra amount theo VND (khong nhan 100 nhu VNPAY).
        return isset($payload['amount']) ? (int) $payload['amount'] : null;
    }

    public function isSuccessful(array $payload): bool
    {
        // resultCode 0 = thanh cong (chuan MOMO).
        return (string) ($payload['resultCode'] ?? '') === '0';
    }
}
