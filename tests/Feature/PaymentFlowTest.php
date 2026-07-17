<?php

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Student;
use App\Models\User;
use App\Services\Payment\PaymentService;
use App\Services\Payment\VnpayGateway;
use Illuminate\Support\Facades\Http;

/*
 * Ticket R3 — luong thanh toan VNPAY that (credentials sandbox trong config test).
 * DoD: verify signature · idempotent IPN · chong gia mao amount.
 */

beforeEach(function () {
    // Cau hinh VNPAY cho test (khong phu thuoc .env).
    config([
        'payment.default' => 'vnpay',
        'payment.vnpay.tmn_code' => 'GIRMGSD6',
        'payment.vnpay.hash_secret' => 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2',
        'payment.vnpay.pay_url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
    ]);

    $this->seed(\Database\Seeders\AiProviderSeeder::class);

    $user = User::create([
        'name' => 'HS', 'email' => 'hs.pay@hoctoan.test', 'password' => 'password', 'role' => 'student',
    ]);
    $this->student = Student::create([
        'user_id' => $user->id, 'full_name' => 'HS', 'grade' => 8, 'status' => 'learning',
    ]);
    $this->user = $user;

    $this->plan = Plan::create([
        'name' => 'Gói 1 tháng', 'price' => 199000, 'duration_days' => 30, 'is_active' => true,
    ]);
});

/** Tao payload IPN da ky dung (mo phong VNPAY goi ve). */
function signedVnpayIpn(string $orderId, int $amount, string $secret, string $responseCode = '00'): array
{
    $data = [
        'vnp_TmnCode' => 'GIRMGSD6',
        'vnp_Amount' => $amount * 100,
        'vnp_TxnRef' => $orderId,
        'vnp_ResponseCode' => $responseCode,
        'vnp_TransactionStatus' => $responseCode,
        'vnp_PayDate' => now()->format('YmdHis'),
    ];
    ksort($data);
    $data['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($data), $secret);

    return $data;
}

it('R3: checkout tao giao dich pending + redirect sang VNPAY sandbox', function () {
    $res = $this->actingAs($this->user)
        ->post(route('payment.checkout', $this->plan))
        ->assertRedirect();

    // Redirect toi dung URL sandbox VNPAY co chu ky.
    expect($res->headers->get('Location'))->toStartWith('https://sandbox.vnpayment.vn/paymentv2/vpcpay.html')
        ->and($res->headers->get('Location'))->toContain('vnp_SecureHash=')
        ->and($res->headers->get('Location'))->toContain('vnp_TmnCode=GIRMGSD6');

    // Co giao dich pending.
    expect(Payment::where('student_id', $this->student->id)->where('status', 'pending')->count())->toBe(1);
});

it('R3: URL thanh toan ky dung bang secret that -> verify lai chinh no pass', function () {
    $gw = new VnpayGateway('GIRMGSD6', 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    $url = $gw->createPayment('ORDER1', 199000, 'https://app.test/return');

    // Tach query, verify lai chu ky trong URL.
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    expect($gw->verifyCallback($params))->toBeTrue();
});

it('DoD R3: IPN chu ky hop le + tien khop -> danh dau paid', function () {
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan));
    $payment = Payment::where('student_id', $this->student->id)->first();

    $ipn = signedVnpayIpn($payment->order_id, $payment->amount, 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2');

    $this->get(route('payment.vnpay-ipn', $ipn))
        ->assertOk()
        ->assertJsonPath('RspCode', '00');

    expect($payment->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->paid_at)->not->toBeNull();
});

it('DoD R3: IPN chu ky SAI -> tu choi (RspCode 97), khong danh dau paid', function () {
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan));
    $payment = Payment::where('student_id', $this->student->id)->first();

    $ipn = signedVnpayIpn($payment->order_id, $payment->amount, 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2');
    $ipn['vnp_SecureHash'] = 'gia-mao-chu-ky';

    $this->get(route('payment.vnpay-ipn', $ipn))
        ->assertOk()
        ->assertJsonPath('RspCode', '97');

    expect($payment->fresh()->status)->toBe('pending');   // van pending
});

it('DoD R3: gia mao AMOUNT (ky lai voi tien khac) -> tu choi RspCode 04', function () {
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan));
    $payment = Payment::where('student_id', $this->student->id)->first();

    // Ke tan cong ky HOP LE nhung voi so tien 1000 thay vi 199000.
    $ipn = signedVnpayIpn($payment->order_id, 1000, 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2');

    $this->get(route('payment.vnpay-ipn', $ipn))
        ->assertOk()
        ->assertJsonPath('RspCode', '04');   // invalid amount

    expect($payment->fresh()->status)->toBe('pending');
});

it('DoD R3: IPN idempotent — goi 2 lan khong xu ly lai (RspCode 02)', function () {
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan));
    $payment = Payment::where('student_id', $this->student->id)->first();
    $ipn = signedVnpayIpn($payment->order_id, $payment->amount, 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2');

    // Lan 1 -> 00
    $this->get(route('payment.vnpay-ipn', $ipn))->assertJsonPath('RspCode', '00');
    // Lan 2 -> 02 (da xu ly)
    $this->get(route('payment.vnpay-ipn', $ipn))->assertJsonPath('RspCode', '02');

    expect($payment->fresh()->status)->toBe('paid');
});

it('R3: order khong ton tai -> RspCode 01', function () {
    $ipn = signedVnpayIpn('KHONG_CO', 199000, 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2');

    $this->get(route('payment.vnpay-ipn', $ipn))->assertJsonPath('RspCode', '01');
});

it('R3: giao dich that bai (ResponseCode 24) -> danh dau failed', function () {
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan));
    $payment = Payment::where('student_id', $this->student->id)->first();

    $ipn = signedVnpayIpn($payment->order_id, $payment->amount, 'IWT1QYC8CIOW3K2XL4K7C7C56S334DW2', '24');

    $this->get(route('payment.vnpay-ipn', $ipn))->assertJsonPath('RspCode', '00');

    expect($payment->fresh()->status)->toBe('failed');
});

// ---------- MOMO ----------

function signedMomoIpn(string $orderId, int $amount, string $secret, string $resultCode = '0'): array
{
    $data = [
        'partnerCode' => 'MOMO_TEST', 'orderId' => $orderId, 'requestId' => $orderId.'-1',
        'amount' => (string) $amount, 'orderInfo' => "Thanh toan don {$orderId}",
        'orderType' => 'momo_wallet', 'transId' => '99999', 'resultCode' => $resultCode,
        'message' => 'ok', 'payType' => 'qr', 'responseTime' => '1700000000', 'extraData' => '',
    ];

    $raw = "accessKey=momo-access&amount={$data['amount']}&extraData=&message={$data['message']}"
        ."&orderId={$data['orderId']}&orderInfo={$data['orderInfo']}&orderType={$data['orderType']}"
        ."&partnerCode={$data['partnerCode']}&payType={$data['payType']}&requestId={$data['requestId']}"
        ."&responseTime={$data['responseTime']}&resultCode={$data['resultCode']}&transId={$data['transId']}";
    $data['signature'] = hash_hmac('sha256', $raw, $secret);

    return $data;
}

function configMomo(): void
{
    config([
        'payment.default' => 'momo',
        'payment.momo.partner_code' => 'MOMO_TEST',
        'payment.momo.access_key' => 'momo-access',
        'payment.momo.secret_key' => 'momo-secret-key',
        'payment.momo.pay_url' => 'https://test-payment.momo.vn/pay',
    ]);

    // MOMO create API doi POST server-to-server -> mock tra payUrl.
    Http::fake([
        'test-payment.momo.vn/*' => Http::response([
            'resultCode' => 0,
            'message' => 'Success',
            'payUrl' => 'https://test-payment.momo.vn/pay/redirect-abc123',
        ]),
    ]);
}

it('R3 MOMO: checkout tao giao dich momo + redirect', function () {
    configMomo();

    $res = $this->actingAs($this->user)
        ->post(route('payment.checkout', $this->plan), ['gateway' => 'momo'])
        ->assertRedirect();

    expect($res->headers->get('Location'))->toContain('momo')
        ->and(Payment::where('gateway', 'momo')->where('status', 'pending')->count())->toBe(1);
});

it('R3 MOMO: API tra loi (resultCode != 0) -> quay lai pricing bao loi, khong 500', function () {
    config([
        'payment.default' => 'momo',
        'payment.momo.partner_code' => 'MOMO_TEST',
        'payment.momo.access_key' => 'momo-access',
        'payment.momo.secret_key' => 'momo-secret-key',
        'payment.momo.pay_url' => 'https://test-payment.momo.vn/pay',
    ]);
    // MoMo tra loi cau hinh (vd sai chu ky/format).
    Http::fake([
        'test-payment.momo.vn/*' => Http::response(['resultCode' => 20, 'message' => 'Bad format request.']),
    ]);

    $this->actingAs($this->user)
        ->post(route('payment.checkout', $this->plan), ['gateway' => 'momo'])
        ->assertRedirect(route('pricing'))
        ->assertSessionHasErrors('gateway');

    // Giao dich pending duoc tao nhung khong redirect sang cong loi.
    expect(Payment::where('gateway', 'momo')->where('status', 'pending')->exists())->toBeTrue();
});

it('DoD R3 MOMO: IPN chu ky hop le + tien khop -> paid', function () {
    configMomo();
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan), ['gateway' => 'momo']);
    $payment = Payment::where('gateway', 'momo')->first();

    $ipn = signedMomoIpn($payment->order_id, $payment->amount, 'momo-secret-key');

    $this->postJson(route('payment.momo-ipn'), $ipn)->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('DoD R3 MOMO: IPN chu ky sai -> tu choi, khong paid', function () {
    configMomo();
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan), ['gateway' => 'momo']);
    $payment = Payment::where('gateway', 'momo')->first();

    $ipn = signedMomoIpn($payment->order_id, $payment->amount, 'momo-secret-key');
    $ipn['signature'] = 'gia-mao';

    $this->postJson(route('payment.momo-ipn'), $ipn)->assertStatus(400);

    expect($payment->fresh()->status)->toBe('pending');
});

it('R3 MOMO: gia mao amount -> tu choi', function () {
    configMomo();
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan), ['gateway' => 'momo']);
    $payment = Payment::where('gateway', 'momo')->first();

    // Ky hop le nhung amount = 1000 thay vi 199000.
    $ipn = signedMomoIpn($payment->order_id, 1000, 'momo-secret-key');

    $this->postJson(route('payment.momo-ipn'), $ipn)->assertStatus(400);
    expect($payment->fresh()->status)->toBe('pending');
});

it('R3 MOMO: IPN idempotent', function () {
    configMomo();
    $this->actingAs($this->user)->post(route('payment.checkout', $this->plan), ['gateway' => 'momo']);
    $payment = Payment::where('gateway', 'momo')->first();
    $ipn = signedMomoIpn($payment->order_id, $payment->amount, 'momo-secret-key');

    $this->postJson(route('payment.momo-ipn'), $ipn)->assertOk();
    // Lan 2 -> da xu ly (code 02 -> HTTP 400 vi khong phai 00).
    $this->postJson(route('payment.momo-ipn'), $ipn)->assertStatus(400);

    expect($payment->fresh()->status)->toBe('paid');
});

it('R3: chon gateway khong hop le -> 422', function () {
    $this->actingAs($this->user)
        ->post(route('payment.checkout', $this->plan), ['gateway' => 'bitcoin'])
        ->assertStatus(422);
});
