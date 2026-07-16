@extends('errors.layout')
@section('code', $success ? '✓' : '✕')
@section('title', $success ? 'Thanh toán thành công' : 'Thanh toán chưa thành công')
@section('message',
    $success
        ? 'Cảm ơn bạn! Gói học đã được kích hoạt.'
        : 'Giao dịch chưa hoàn tất. Nếu đã bị trừ tiền, hệ thống sẽ tự đối soát — bạn không cần thanh toán lại.')
