<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends API_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->model('Payment_model');
  }

  public function index()
  {
    // Validasi token login pembeli agar aman
    $auth = $this->verify_token();
    if ($auth !== true) return $auth;

    $methods = $this->Payment_model->get_active_methods();

    if (!empty($methods)) {
      return json_response(true, 'Data metode pembayaran berhasil diambil!', $methods);
    }

    return json_response(false, 'Data metode pembayaran belum tersedia.', [], 404);
  }
}
