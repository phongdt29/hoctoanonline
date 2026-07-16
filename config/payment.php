<?php

/*
|--------------------------------------------------------------------------
| Cau hinh cong thanh toan (Ticket R3)
|--------------------------------------------------------------------------
| Secret KHONG BAO GIO hardcode o day — doc tu .env (gitignored).
| Sandbox VNPAY dung cho kiem thu; production thay bang credential that.
*/

return [

    'default' => env('PAYMENT_GATEWAY', 'vnpay'),

    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'pay_url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    ],

    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'pay_url' => env('MOMO_URL', 'https://test-payment.momo.vn/v2/gateway/api/create'),
    ],

];
