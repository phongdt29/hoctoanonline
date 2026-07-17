<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ticket R3 — luong thanh toan VNPAY. */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /** GET /pricing — trang chon goi hoc. */
    public function pricing(\App\Services\Payment\PaymentGatewayFactory $factory): View
    {
        return view('payment.pricing', [
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
            'gateways' => $factory->configured(),   // chi hien cong da cau hinh
            'themeColor' => request()->user()?->student?->favorite_color,
        ]);
    }

    /** POST /payment/checkout/{plan} — tao giao dich + redirect sang cong (vnpay|momo). */
    public function checkout(Request $request, Plan $plan): RedirectResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403);
        abort_unless($plan->is_active, 404);

        $gateway = $request->input('gateway', config('payment.default'));
        // Chi cho thanh toan qua cong DA cau hinh (co credentials) — tranh redirect
        // sang cong loi "Bad format request".
        abort_unless(
            app(\App\Services\Payment\PaymentGatewayFactory::class)->isConfigured($gateway),
            422,
            'Cổng thanh toán này chưa được cấu hình. Vui lòng chọn cổng khác.',
        );

        try {
            $result = $this->payments->initiate(
                $student,
                $plan,
                route('payment.return'),
                $request->ip(),
                $gateway,
            );
        } catch (\Throwable $e) {
            // Cong loi (vd MoMo tra resultCode != 0) -> quay lai pricing bao loi,
            // KHONG de 500 tho ra man hinh.
            report($e);

            return redirect()->route('pricing')
                ->with('status', null)
                ->withErrors(['gateway' => 'Không kết nối được cổng thanh toán lúc này. Vui lòng thử lại hoặc chọn cổng khác.']);
        }

        return redirect()->away($result['pay_url']);
    }

    /**
     * GET /payment/return — VNPAY redirect nguoi dung ve day sau khi thanh toan.
     * Chi de HIEN THI ket qua; cap nhat trang thai THAT lam o IPN (server-to-server).
     */
    public function return(Request $request): View
    {
        $result = $this->payments->handleCallback($request->query(), 'vnpay');
        $orderId = $request->query('vnp_TxnRef');
        $payment = Payment::where('order_id', $orderId)->first();

        return view('payment.result', [
            'success' => $result['code'] === '00',
            'payment' => $payment,
        ]);
    }

    /**
     * GET /payment/vnpay-ipn — IPN server-to-server VNPAY.
     * KHONG auth (VNPAY khong co session), bao ve bang verify signature.
     */
    public function vnpayIpn(Request $request): JsonResponse
    {
        $result = $this->payments->handleCallback($request->query(), 'vnpay');

        return response()->json([
            'RspCode' => $result['code'],
            'Message' => $result['message'],
        ]);
    }

    /**
     * POST /payment/momo-ipn — IPN server-to-server MOMO.
     * MOMO gui JSON body (khong phai query). Tra 204 khi da nhan (chuan MOMO).
     */
    public function momoIpn(Request $request): JsonResponse
    {
        $result = $this->payments->handleCallback($request->all(), 'momo');

        // MOMO chi can HTTP 2xx de biet da nhan IPN.
        return response()->json(['message' => $result['message']], $result['code'] === '00' ? 200 : 400);
    }
}
