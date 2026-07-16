<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Ticket R3 — dieu phoi thanh toan: tao giao dich, xu ly callback (idempotent). */
class PaymentService
{
    public function __construct(private readonly PaymentGatewayFactory $factory) {}

    /** Tao giao dich pending + URL thanh toan de redirect nguoi dung. */
    public function initiate(Student $student, Plan $plan, string $returnUrl, string $ip, ?string $gatewayName = null): array
    {
        $gatewayName ??= config('payment.default');
        $orderId = 'HT'.now()->format('YmdHis').Str::upper(Str::random(4));

        $payment = Payment::create([
            'student_id' => $student->id,
            'plan_id' => $plan->id,
            'order_id' => $orderId,
            'amount' => $plan->price,
            'gateway' => $gatewayName,
            'status' => Payment::STATUS_PENDING,
        ]);

        $gateway = $this->factory->make($gatewayName);
        $url = $gateway->createPayment($orderId, $plan->price, $returnUrl, $ip);

        return ['payment' => $payment, 'pay_url' => $url];
    }

    /**
     * Xu ly callback/IPN cho MOT cong cu the. VERIFY SIGNATURE truoc —
     * khong tin payload vo dieu kien. Idempotent (cong co the goi IPN nhieu lan).
     *
     * Ma tra ve dung chung 2 cong: 00 ok · 97 sai chu ky · 01 khong tim thay ·
     * 04 sai tien · 02 da xu ly.
     *
     * @param  string  $gatewayName  'vnpay' | 'momo' — do route quyet dinh, khong tu payload.
     * @return array{code: string, message: string}
     */
    public function handleCallback(array $payload, ?string $gatewayName = null): array
    {
        $gatewayName ??= config('payment.default');
        $gateway = $this->factory->make($gatewayName);

        // 1. Verify chu ky.
        if (! $gateway->verifyCallback($payload)) {
            return ['code' => '97', 'message' => 'Invalid signature'];
        }

        // 2. Tim giao dich (dung cong da luu -> chong dung nham cong khac).
        $orderId = $gateway->extractOrderId($payload);
        $payment = Payment::where('order_id', $orderId)
            ->where('gateway', $gatewayName)
            ->first();

        if (! $payment) {
            return ['code' => '01', 'message' => 'Order not found'];
        }

        // 3. Kiem tra so tien khop (chong gia mao amount du da verify chu ky).
        if ($gateway->extractAmount($payload) !== $payment->amount) {
            return ['code' => '04', 'message' => 'Invalid amount'];
        }

        // 4. Idempotent: da xu ly roi thi tra OK, khong ghi lai.
        if ($payment->status !== Payment::STATUS_PENDING) {
            return ['code' => '02', 'message' => 'Order already confirmed'];
        }

        // 5. Cap nhat trang thai.
        DB::transaction(function () use ($payment, $payload, $gateway) {
            $payment->update([
                'status' => $gateway->isSuccessful($payload)
                    ? Payment::STATUS_PAID
                    : Payment::STATUS_FAILED,
                'callback_payload' => $payload,
                'paid_at' => $gateway->isSuccessful($payload) ? now() : null,
            ]);
        });

        return ['code' => '00', 'message' => 'Confirm Success'];
    }
}
