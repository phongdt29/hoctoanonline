<?php

use App\Services\Payment\MomoGateway;
use App\Services\Payment\VnpayGateway;

/*
 * Ticket R3 — DoD: verify signature (khong tin callback vo dieu kien).
 * Test o Unit vi thuan logic ky, khong cham DB.
 */

function vnpay(): VnpayGateway
{
    return new VnpayGateway('TMN123', 'secret-hash-key', 'https://sandbox.vnpay.test/pay');
}

it('R3 VNPAY: tao URL thanh toan co chu ky', function () {
    $url = vnpay()->createPayment('ORDER1', 100000, 'https://app.test/return');

    expect($url)->toContain('vnp_SecureHash=')
        ->and($url)->toContain('vnp_TxnRef=ORDER1')
        ->and($url)->toContain('vnp_Amount=10000000');   // *100
});

it('DoD R3 VNPAY: callback chu ky HOP LE -> verify true', function () {
    $gw = vnpay();

    // Tao payload va ky dung cach (mo phong cong gui ve).
    $data = [
        'vnp_TxnRef' => 'ORDER1',
        'vnp_ResponseCode' => '00',
        'vnp_Amount' => '10000000',
    ];
    ksort($data);
    $sig = hash_hmac('sha512', http_build_query($data), 'secret-hash-key');
    $data['vnp_SecureHash'] = $sig;

    expect($gw->verifyCallback($data))->toBeTrue()
        ->and($gw->isSuccessful($data))->toBeTrue()
        ->and($gw->extractOrderId($data))->toBe('ORDER1');
});

it('DoD R3 VNPAY: callback chu ky SAI -> verify false (khong tin)', function () {
    $gw = vnpay();

    $data = [
        'vnp_TxnRef' => 'ORDER1',
        'vnp_ResponseCode' => '00',
        'vnp_Amount' => '10000000',
        'vnp_SecureHash' => 'chu-ky-gia-mao',
    ];

    expect($gw->verifyCallback($data))->toBeFalse();
});

it('R3 VNPAY: sua amount sau khi ky -> verify false (chong gia mao so tien)', function () {
    $gw = vnpay();

    $data = ['vnp_TxnRef' => 'ORDER1', 'vnp_Amount' => '10000000'];
    ksort($data);
    $data['vnp_SecureHash'] = hash_hmac('sha512', http_build_query($data), 'secret-hash-key');

    // Ke tan cong sua amount sau khi da ky.
    $data['vnp_Amount'] = '1';

    expect($gw->verifyCallback($data))->toBeFalse();
});

it('R3 VNPAY: khong co chu ky -> verify false', function () {
    expect(vnpay()->verifyCallback(['vnp_TxnRef' => 'X']))->toBeFalse();
});

function momo(): MomoGateway
{
    return new MomoGateway('MOMO123', 'access-key', 'momo-secret', 'https://sandbox.momo.test/pay');
}

it('DoD R3 MOMO: callback chu ky hop le -> verify true', function () {
    $gw = momo();

    $payload = [
        'partnerCode' => 'MOMO123', 'orderId' => 'ORDER9', 'requestId' => 'REQ9',
        'amount' => '50000', 'orderInfo' => 'Thanh toan don ORDER9', 'orderType' => 'momo_wallet',
        'transId' => '123', 'resultCode' => '0', 'message' => 'Success', 'payType' => 'qr',
        'responseTime' => '1700000000', 'extraData' => '',
    ];

    $raw = "accessKey=access-key&amount=50000&extraData=&message=Success&orderId=ORDER9"
        .'&orderInfo=Thanh toan don ORDER9&orderType=momo_wallet&partnerCode=MOMO123'
        .'&payType=qr&requestId=REQ9&responseTime=1700000000&resultCode=0&transId=123';
    $payload['signature'] = hash_hmac('sha256', $raw, 'momo-secret');

    expect($gw->verifyCallback($payload))->toBeTrue()
        ->and($gw->isSuccessful($payload))->toBeTrue()
        ->and($gw->extractOrderId($payload))->toBe('ORDER9');
});

it('R3 MOMO: chu ky sai -> verify false', function () {
    expect(momo()->verifyCallback(['orderId' => 'X', 'signature' => 'gia']))->toBeFalse();
});

it('R3 MOMO: resultCode khac 0 -> khong thanh cong', function () {
    expect(momo()->isSuccessful(['resultCode' => '1006']))->toBeFalse();
});

it('R3: ca 2 cong deu implement interface PaymentGateway', function () {
    expect(vnpay())->toBeInstanceOf(\App\Services\Payment\PaymentGateway::class)
        ->and(momo())->toBeInstanceOf(\App\Services\Payment\PaymentGateway::class);
});
