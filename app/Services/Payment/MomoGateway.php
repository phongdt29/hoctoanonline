<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Ticket R3 — cong MOMO (API v2, requestType captureWallet).
 * Chu ky HMAC-SHA256 tren chuoi raw theo thu tu field CO DINH cua MOMO.
 *
 * Khac VNPAY (tao URL truc tiep): MOMO doi POST server-to-server toi API create,
 * MOMO tra ve `payUrl` de redirect. Vi vay createPayment goi HTTP that.
 *
 * LUU Y localhost: MOMO goi ipnUrl (server-to-server) nen may chay phai co dia chi
 * PUBLIC. localhost:8000 se KHONG nhan duoc IPN — MoMo chi chay day du khi deploy that.
 */
class MomoGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $partnerCode,
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $payUrl,
    ) {}

    public function createPayment(string $orderId, int $amount, string $returnUrl, ?string $ipnUrl = null): string
    {
        $requestId = $orderId.'-'.now()->timestamp;
        $orderInfo = "Thanh toan don {$orderId}";
        $extraData = '';
        $requestType = 'captureWallet';
        // MOMO goi IPN server-to-server; neu khong truyen thi dung route momo-ipn.
        $ipnUrl ??= route('payment.momo-ipn');

        // Chuoi ky theo thu tu ALPHABET cac field bat buoc (chuan MOMO create).
        $raw = "accessKey={$this->accessKey}&amount={$amount}&extraData={$extraData}"
            ."&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}"
            ."&partnerCode={$this->partnerCode}&redirectUrl={$returnUrl}"
            ."&requestId={$requestId}&requestType={$requestType}";

        $signature = hash_hmac('sha256', $raw, $this->secretKey);

        // POST JSON len API create cua MOMO -> nhan payUrl.
        $response = Http::timeout(30)->acceptJson()->post($this->payUrl, [
            'partnerCode' => $this->partnerCode,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => $requestType,
            'extraData' => $extraData,
            'lang' => 'vi',
            'signature' => $signature,
        ]);

        $body = $response->json();

        // MOMO tra resultCode 0 + payUrl khi thanh cong; con lai la loi cau hinh/ky.
        if (($body['resultCode'] ?? -1) !== 0 || empty($body['payUrl'])) {
            throw new RuntimeException(
                'MoMo tạo thanh toán thất bại: '.($body['message'] ?? 'lỗi không rõ')
                .' (resultCode='.($body['resultCode'] ?? '?').')'
            );
        }

        return $body['payUrl'];
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
