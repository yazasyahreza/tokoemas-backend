<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Vouchers extends API_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->model('Voucher_model');
  }

  // 1. Endpoint List Voucher untuk Modal Pop-up
  public function index()
  {
    $auth = $this->verify_token();
    if ($auth !== true) return $auth;

    $vouchers = $this->Voucher_model->get_available_vouchers();
    return json_response(true, 'Daftar voucher berhasil dimuat.', $vouchers);
  }

  // 2. Endpoint Validasi Kode Kupon (Input Manual / Klik Modal)
  public function validate()
  {
    $auth = $this->verify_token();
    if ($auth !== true) return $auth;

    $input = $this->get_input();
    $code  = isset($input['code']) ? strtoupper(trim($input['code'])) : '';
    $cart_total = isset($input['cart_total']) ? floatval($input['cart_total']) : 0;

    if (empty($code)) {
      return json_response(false, 'Kode voucher tidak boleh kosong bos!', null, 400);
    }

    // Cek Keberadaan Voucher
    $voucher = $this->Voucher_model->get_voucher_by_code($code);
    if (!$voucher) {
      return json_response(false, 'Waduh, kode voucher tidak terdaftar atau sudah tidak aktif.', null, 404);
    }

    // Cek Validasi Waktu / Masa Berlaku
    $now = time();
    if ($now < strtotime($voucher->start_date) || $now > strtotime($voucher->end_date)) {
      return json_response(false, 'Maaf bos, masa berlaku voucher ini sudah habis/belum mulai.', null, 400);
    }

    // Cek Validasi Kuota Pemakaian Global
    if ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) {
      return json_response(false, 'Sayang sekali bos, kuota pemakaian voucher ini sudah habis diklaim pelanggan lain.', null, 400);
    }

    // Cek Validasi Minimal Belanja
    if ($cart_total < $voucher->min_purchase) {
      return json_response(false, 'Total belanja Anda belum memenuhi syarat minimum untuk voucher ini bos.', null, 400);
    }

    // 🔥 HITUNG NOMINAL POTONGAN ASLI (ANTI-FRAUD LOGIC)
    $discount_amount = 0;
    if ($voucher->discount_type === 'fixed') {
      $discount_amount = floatval($voucher->discount_value);
    } else if ($voucher->discount_type === 'percent') {
      $discount_amount = ($voucher->discount_value / 100) * $cart_total;
      // Jika ada batasan max_discount, lakukan pemotongan mentok di limit tersebut
      if ($voucher->max_discount !== null && $discount_amount > $voucher->max_discount) {
        $discount_amount = floatval($voucher->max_discount);
      }
    }

    // Susun response data yang seragam untuk frontend Vue Bos
    $response_data = [
      'id' => $voucher->id,
      'code' => $voucher->code,
      'title' => $voucher->title,
      'discount' => $discount_amount, // Nilai potongan asli rupiah
      'minSpend' => floatval($voucher->min_purchase)
    ];

    return json_response(true, 'Selamat! Voucher berhasil diterapkan.', $response_data);
  }
}
