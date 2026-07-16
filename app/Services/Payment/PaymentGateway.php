<?php

namespace App\Services\Payment;

/**
 * Ticket R3 (SPEC §8) — interface cong thanh toan.
 *
 * Moi cong (VNPAY, MOMO) implement interface nay. Code nghiep vu chi phu thuoc
 * interface, khong biet cong nao -> them cong moi khong sua logic.
 */
interface PaymentGateway
{
    /** Tao URL thanh toan (redirect nguoi dung sang cong). */
    public function createPayment(string $orderId, int $amount, string $returnUrl): string;

    /**
     * Xac thuc callback/IPN tu cong. TRA VE true CHI KHI chu ky hop le.
     * BAT BUOC verify signature — khong duoc tin callback vo dieu kien.
     *
     * @param  array<string,string>  $payload  toan bo tham so cong gui ve
     */
    public function verifyCallback(array $payload): bool;

    /** Trich orderId tu payload da verify. */
    public function extractOrderId(array $payload): ?string;

    /** Trich so tien (VND) tu payload — de doi chieu chong gia mao. */
    public function extractAmount(array $payload): ?int;

    /** Payload co bao thanh toan thanh cong khong (sau khi da verify). */
    public function isSuccessful(array $payload): bool;
}
