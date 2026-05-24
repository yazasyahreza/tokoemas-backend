<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shipping extends API_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->model('Shipping_model');
  }

  public function index()
  {
    // Pastikan hanya member yang bisa akses tarif pengiriman
    $auth = $this->verify_token();
    if ($auth !== true) return $auth;

    $methods = $this->Shipping_model->get_active_methods();

    if ($methods) {
      return json_response(true, 'Data kurir berhasil diambil', $methods);
    }

    return json_response(false, 'Data kurir kosong atau belum disetting', [], 404);
  }
}
